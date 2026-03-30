<?php

function spp_account_pms_identity_names(array $rows): array
{
    $allIdentityIds = [];
    foreach ($rows as $row) {
        if (!empty($row['sender_identity_id'])) {
            $allIdentityIds[] = (int)$row['sender_identity_id'];
        }
        if (!empty($row['recipient_identity_id'])) {
            $allIdentityIds[] = (int)$row['recipient_identity_id'];
        }
    }

    return function_exists('spp_resolve_identity_names')
        ? spp_resolve_identity_names($allIdentityIds)
        : [];
}

function spp_account_pms_build_timeline_items(array $rows): array
{
    $identityNames = spp_account_pms_identity_names($rows);
    $conversationMap = array();

    foreach ($rows as $row) {
        $isIncoming = (($row['pm_box'] ?? '') === 'in');
        $peerId = $isIncoming ? (int)($row['sender_id'] ?? 0) : (int)($row['owner_id'] ?? 0);

        if ($isIncoming) {
            $identId = (int)($row['sender_identity_id'] ?? 0);
            $peerName = $identId && isset($identityNames[$identId])
                ? $identityNames[$identId]
                : (string)($row['sender'] ?? '');
        } else {
            $identId = (int)($row['recipient_identity_id'] ?? 0);
            $peerName = $identId && isset($identityNames[$identId])
                ? $identityNames[$identId]
                : (string)($row['receiver'] ?? '');
        }

        if ($peerId <= 0 || $peerName === '') {
            continue;
        }

        if (!isset($conversationMap[$peerId])) {
            $conversationMap[$peerId] = array(
                'peer_id' => $peerId,
                'peer_name' => $peerName,
                'latest_id' => (int)$row['id'],
                'latest_message' => (string)($row['message'] ?? ''),
                'latest_posted' => (int)($row['posted'] ?? 0),
                'latest_box' => (string)($row['pm_box'] ?? 'in'),
                'unread_count' => 0,
            );
        }

        if ($isIncoming && empty($row['showed'])) {
            $conversationMap[$peerId]['unread_count']++;
        }
    }

    return array_values($conversationMap);
}

function spp_account_pms_enrich_thread_items(array $threadItems): array
{
    $threadIdentityNames = spp_account_pms_identity_names($threadItems);
    foreach ($threadItems as &$tRow) {
        $sIdentId = (int)($tRow['sender_identity_id'] ?? 0);
        $tRow['sender_display'] = $sIdentId && isset($threadIdentityNames[$sIdentId])
            ? $threadIdentityNames[$sIdentId]
            : (string)($tRow['sender'] ?? '');
        $rIdentId = (int)($tRow['recipient_identity_id'] ?? 0);
        $tRow['receiver_display'] = $rIdentId && isset($threadIdentityNames[$rIdentId])
            ? $threadIdentityNames[$rIdentId]
            : (string)($tRow['receiver'] ?? '');
    }
    unset($tRow);

    return $threadItems;
}
