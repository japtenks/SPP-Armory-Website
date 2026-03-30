<?php

function spp_forum_fetch_identity_meta(PDO $forumPdo, array $rows, string $context = 'forum.read'): array
{
    $posterIdentityMeta = array();
    if (empty($rows)) {
        return $posterIdentityMeta;
    }

    $identityIds = array();
    foreach ($rows as $row) {
        $identityId = (int)($row['poster_identity_id'] ?? 0);
        if ($identityId > 0) {
            $identityIds[] = $identityId;
        }
    }

    $identityIds = array_values(array_unique($identityIds));
    if (empty($identityIds)) {
        return $posterIdentityMeta;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($identityIds), '?'));
        $stmtIdentityMeta = $forumPdo->prepare("
            SELECT identity_id, identity_type, is_bot
            FROM website_identities
            WHERE identity_id IN ({$placeholders})
        ");
        $stmtIdentityMeta->execute($identityIds);
        foreach ($stmtIdentityMeta->fetchAll(PDO::FETCH_ASSOC) as $identityRow) {
            $posterIdentityMeta[(int)$identityRow['identity_id']] = array(
                'identity_type' => (string)($identityRow['identity_type'] ?? ''),
                'is_bot' => (int)($identityRow['is_bot'] ?? 0),
            );
        }
    } catch (Throwable $e) {
        error_log('[' . $context . '] Identity meta lookup failed: ' . $e->getMessage());
    }

    return $posterIdentityMeta;
}

function spp_forum_hydrate_topic_posts(
    PDO $forumPdo,
    PDO $charPdo,
    array $rows,
    int $realmId,
    string $topicLinkBase,
    int $pageOffset = 0,
    bool $includeRichMeta = false
): array {
    $posts = array();
    $posterPostCountCache = array();
    $posterIdentityMeta = spp_forum_fetch_identity_meta($forumPdo, $rows, 'forum.read');
    $postIndex = 0;

    foreach ($rows as $curPost) {
        if ($includeRichMeta) {
            $curPost['linktoprofile'] = $GLOBALS['MW']->getConfig->temp->site_href . 'index.php?n=account&sub=view&action=find&name=' . $curPost['username'];
            $curPost['linktopms'] = $GLOBALS['MW']->getConfig->temp->site_href . 'index.php?n=account&sub=pms&action=add&to=' . $curPost['username'];
            $curPost['linktoedit'] = $GLOBALS['MW']->getConfig->temp->site_href . 'index.php?n=forum&sub=post&action=editpost&post=' . $curPost['post_id'];
            $curPost['linktodelete'] = $GLOBALS['MW']->getConfig->temp->site_href . 'index.php?n=forum&sub=post&action=dodeletepost&post=' . $curPost['post_id'];
        }

        $curPost['linktothis'] = $topicLinkBase . '&to=' . $curPost['post_id'];
        if (!empty($curPost['poster_character_id'])) {
            $curPost['linktocharacter_social'] = $GLOBALS['MW']->getConfig->temp->site_href
                . 'index.php?n=server&sub=character&realm=' . (int)$realmId
                . '&guid=' . (int)$curPost['poster_character_id']
                . '&tab=social';
        } else {
            $curPost['linktocharacter_social'] = $curPost['linktoprofile'] ?? '';
        }

        $curPost['avatar'] = '';
        $stmtChar = $charPdo->prepare("
            SELECT c.race, c.class, c.level, c.gender, g.name AS guild
            FROM characters c
            LEFT JOIN guild_member gm ON c.guid = gm.guid
            LEFT JOIN guild g ON gm.guildid = g.guildid
            WHERE c.guid = ?
        ");
        $stmtChar->execute([(int)$curPost['poster_character_id']]);
        $charinfo = $stmtChar->fetch(PDO::FETCH_ASSOC);

        $uploadedAvatar = '';
        if (!empty($curPost['website_avatar'])) {
            $uploadedAvatar = (string)$curPost['website_avatar'];
        }

        if ($uploadedAvatar !== '') {
            $curPost['avatar'] = 'images/avatars/' . rawurlencode(basename($uploadedAvatar));
        }

        if (!empty($curPost['identity_signature'])) {
            $curPost['signature'] = $curPost['identity_signature'];
        } elseif (!empty($curPost['website_signature'])) {
            $curPost['signature'] = $curPost['website_signature'];
        }

        if ($curPost['avatar'] === '' && !empty($charinfo)) {
            $curPost['avatar'] = get_character_portrait_path(
                $curPost['poster_character_id'],
                $charinfo['gender'],
                $charinfo['race'],
                $charinfo['class']
            );
        }

        if (!empty($charinfo)) {
            $curPost['level'] = (int)$charinfo['level'];
            $curPost['guild'] = $charinfo['guild'] ?? '';
            if ($includeRichMeta) {
                $curPost['mini_race'] = $charinfo['race'] . '-' . $charinfo['gender'] . '.gif';
                $curPost['mini_class'] = $charinfo['class'] . '.gif';
                $curPost['faction'] = in_array((int)$charinfo['race'], array(1, 3, 4, 7, 11), true) ? 'alliance.gif' : 'horde.gif';
            }
        } else {
            if ($curPost['avatar'] === '') {
                $curPost['avatar'] = get_forum_avatar_fallback($curPost['poster'] ?? '');
            }
            $curPost['level'] = 0;
            $curPost['guild'] = '';
            if ($includeRichMeta) {
                $curPost['mini_race'] = '';
                $curPost['mini_class'] = '';
                $curPost['faction'] = '';
            }
        }

        $posterId = (int)($curPost['poster_id'] ?? 0);
        $posterIdentityId = (int)($curPost['poster_identity_id'] ?? 0);
        $identityMeta = $posterIdentityMeta[$posterIdentityId] ?? null;
        $countByIdentity = $posterIdentityId > 0 && !empty($identityMeta)
            && (((int)$identityMeta['is_bot']) === 1 || ($identityMeta['identity_type'] ?? '') === 'bot_character');

        if ($countByIdentity) {
            $cacheKey = 'identity:' . $posterIdentityId;
            if (!isset($posterPostCountCache[$cacheKey])) {
                $stmtPostCount = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_identity_id = ?");
                $stmtPostCount->execute([$posterIdentityId]);
                $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
            }
            $curPost['forum_post_count'] = $posterPostCountCache[$cacheKey];
        } elseif ($posterId > 0) {
            $cacheKey = 'account:' . $posterId;
            if (!isset($posterPostCountCache[$cacheKey])) {
                $stmtPostCount = $forumPdo->prepare("SELECT COUNT(*) FROM f_posts WHERE poster_id = ?");
                $stmtPostCount->execute([$posterId]);
                $posterPostCountCache[$cacheKey] = (int)$stmtPostCount->fetchColumn();
            }
            $curPost['forum_post_count'] = $posterPostCountCache[$cacheKey];
        } else {
            $curPost['forum_post_count'] = 0;
        }

        $postIndex++;
        $curPost['pos_num'] = $pageOffset + $postIndex;

        $postedTs = (int)($curPost['posted'] ?? 0);
        if ($includeRichMeta) {
            global $lang, $yesterday_ts;
            if (date('d', $postedTs) == date('d') && $_SERVER['REQUEST_TIME'] - $postedTs < 86400) {
                $curPost['posted'] = rtrim((string)$lang['today_at']) . ' ' . date('H:i:s', $postedTs);
            } elseif (date('d', $postedTs) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $postedTs < 2 * 86400) {
                $curPost['posted'] = rtrim((string)$lang['yesterday_at']) . ' ' . date('H:i:s', $postedTs);
            } else {
                $curPost['posted'] = date('M d, Y H:i:s', $postedTs);
            }
        }

        $rawMessage = (string)($curPost['message'] ?? '');
        $normalizedMessage = str_replace(
            array('<br />', '<br/>', '<br>'),
            "\n",
            html_entity_decode($rawMessage, ENT_QUOTES, 'UTF-8')
        );
        $normalizedMessage = spp_forum_normalize_legacy_markup($normalizedMessage);
        $curPost['rendered_message'] = bbcode($normalizedMessage, true, true, true, false);

        if ($includeRichMeta) {
            $rawSignature = (string)($curPost['signature'] ?? '');
            if (!empty($rawSignature)) {
                $normalizedSignature = str_replace(
                    array('<br />', '<br/>', '<br>'),
                    "\n",
                    html_entity_decode($rawSignature, ENT_QUOTES, 'UTF-8')
                );
                $normalizedSignature = spp_forum_normalize_legacy_markup($normalizedSignature);
                $curPost['rendered_signature'] = bbcode($normalizedSignature, true, true, true, false);
            } else {
                $curPost['rendered_signature'] = '';
            }
        }

        $posts[] = $curPost;
    }

    return $posts;
}

function spp_forum_build_index_items(PDO $realmPdo, array $user): array
{
    global $lang, $yesterday_ts, $realmDbMap;

    if (($user['id'] ?? 0) > 0) {
        $queryparts = "
            SELECT f_categories.*,f_forums.*,f_topics.topic_name,f_topics.last_poster,f_topics.last_post,f_markread.* FROM f_categories
            JOIN f_forums ON f_categories.cat_id=f_forums.cat_id
            LEFT JOIN f_topics ON f_forums.last_topic_id=f_topics.topic_id
            LEFT JOIN f_markread ON (f_markread.marker_forum_id=f_forums.forum_id AND f_markread.marker_member_id=?)
        ";
        $queryParams = [(int)$user['id']];
    } else {
        $queryparts = "
            SELECT f_categories.*,f_forums.*,f_topics.topic_name,f_topics.last_poster,f_topics.last_post FROM f_categories
            JOIN f_forums ON f_categories.cat_id=f_forums.cat_id
            LEFT JOIN f_topics ON f_forums.last_topic_id=f_topics.topic_id
        ";
        $queryParams = [];
    }

    if (($user['g_forum_moderate'] ?? 0) != 1) {
        $queryparts .= " WHERE hidden!=1 ";
    }
    $queryparts .= " ORDER BY cat_disp_position,cat_name,disp_position,forum_name ";

    $stmt = $realmPdo->prepare($queryparts);
    $stmt->execute($queryParams);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $items = array();
    foreach ($result as $item) {
        if (($user['id'] ?? 0) > 0) {
            if (($item['last_post'] ?? 0) > ($item['marker_last_cleared'] ?? 0)) {
                $item['isnew'] = true;
            } else {
                $item['isnew'] = ((int)($item['marker_unread'] ?? 0) > 0);
            }
        } else {
            $item['isnew'] = true;
        }

        $lastPostTs = (int)($item['last_post'] ?? 0);
        if (date('d', $lastPostTs) == date('d') && $_SERVER['REQUEST_TIME'] - $lastPostTs < 86400) {
            $item['last_post'] = $lang['today_at'] . ' ' . date('H:i:s', $lastPostTs);
        } elseif (date('d', $lastPostTs) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $lastPostTs < 2 * 86400) {
            $item['last_post'] = $lang['yesterday_at'] . ' ' . date('H:i:s', $lastPostTs);
        } else {
            $item['last_post'] = date('d-m-Y, H:i:s', $lastPostTs);
        }

        $item['linktothis'] = mw_url("forum", "viewforum", array("fid" => $item['forum_id']));
        $item['linktolastpost'] = mw_url("forum", "viewtopic", array("tid" => $item['last_topic_id'], "to" => "lastpost"));
        $item['linktoprofile'] = mw_url("account", "view", array("action" => "find", "name" => $item['last_poster']));

        $items[$item['cat_id']][] = $item;
    }

    return $items;
}

function spp_forum_prepare_viewforum_marker(PDO $vfPdo, array $user, array $forum): array
{
    $topicsmark = array();
    $mark = array(
        'marker_topics_read' => serialize(array()),
        'marker_last_update' => 0,
        'marker_unread' => 0,
        'marker_last_cleared' => 0,
    );

    if (($user['id'] ?? 0) <= 0) {
        return array($topicsmark, $mark);
    }

    if (($_GETVARS['markread'] ?? null) == 1) {
        $stmtMr = $vfPdo->prepare("UPDATE f_markread SET marker_topics_read=?,marker_last_update=?,marker_unread=0,marker_last_cleared=? WHERE marker_member_id=? AND marker_forum_id=?");
        $stmtMr->execute([serialize($topicsmark), (int)$_SERVER['REQUEST_TIME'], (int)$_SERVER['REQUEST_TIME'], (int)$user['id'], (int)$forum['forum_id']]);
        redirect($GLOBALS['MW']->getConfig->temp->site_href . 'index.php?n=forum&sub=viewforum&fid=' . $forum['forum_id'], 1);
        return array($topicsmark, $mark);
    }

    $stmtGetMr = $vfPdo->prepare("SELECT * FROM f_markread WHERE marker_member_id=? AND marker_forum_id=?");
    $stmtGetMr->execute([(int)$user['id'], (int)$forum['forum_id']]);
    $mark = $stmtGetMr->fetch(PDO::FETCH_ASSOC);
    if (!$mark) {
        $stmtInsMr = $vfPdo->prepare("INSERT INTO f_markread SET marker_member_id=?,marker_forum_id=?,marker_topics_read=?");
        $stmtInsMr->execute([(int)$user['id'], (int)$forum['forum_id'], serialize(array())]);
    }
    if (!empty($mark['marker_topics_read'])) {
        $topicsmark = unserialize($mark['marker_topics_read']);
    }

    return array($topicsmark, $mark ?: array(
        'marker_topics_read' => serialize(array()),
        'marker_last_update' => 0,
        'marker_unread' => 0,
        'marker_last_cleared' => 0,
    ));
}

function spp_forum_build_viewforum_topics(
    PDO $vfPdo,
    array $forum,
    array $user,
    array $topicsmark,
    array $mark,
    int $itemsPerPage,
    int $limitStart,
    string $sortField,
    string $sortDir
): array {
    global $MW, $lang, $yesterday_ts;

    $allowedSortFields = array(
        'subject' => 'f_topics.topic_name',
        'author' => 'topic_author_display',
        'posted' => 'f_topics.topic_posted',
        'replies' => 'f_topics.num_replies',
        'views' => 'f_topics.num_views',
        'last_reply' => 'f_topics.last_post',
    );

    $stmtAt = $vfPdo->prepare("
        SELECT f_topics.*,account.username,
               COALESCE(NULLIF(f_topics.topic_poster, ''), account.username) AS topic_author_display
        FROM f_topics
        LEFT JOIN account ON f_topics.topic_poster_id=account.id
        WHERE forum_id=?
        ORDER BY sticky DESC, " . $allowedSortFields[$sortField] . " " . $sortDir . ", f_topics.last_post DESC, f_topics.topic_id DESC
        LIMIT " . (int)$limitStart . "," . (int)$itemsPerPage);
    $stmtAt->execute([(int)$forum['forum_id']]);
    $alltopics = $stmtAt->fetchAll(PDO::FETCH_ASSOC);

    $topics = array();
    foreach ($alltopics as $cur_topic) {
        $topicLastRead = isset($topicsmark[$cur_topic['topic_id']]) ? (int)$topicsmark[$cur_topic['topic_id']] : 0;
        if (($user['id'] ?? 0) > 0 && $cur_topic['last_post'] > (int)$mark['marker_last_cleared']) {
            $cur_topic['isnew'] = $cur_topic['last_post'] > $topicLastRead;
        } else {
            $cur_topic['isnew'] = true;
        }

        $pnum = max(1, (int)ceil(((int)$cur_topic['num_replies'] + 1) / (int)$MW->getConfig->generic->posts_per_page));
        if ($pnum > 1) {
            $cur_topic['pages_str'] = '&laquo; ';
            for ($pi = 1; $pi <= $pnum; $pi++) {
                $cur_topic['pages_str'] .= '<a href="index.php?n=forum&sub=viewtopic&tid=' . $cur_topic['topic_id'] . '&p=' . $pi . '">' . $pi . '</a> ';
            }
            $cur_topic['pages_str'] .= ' &raquo;';
        }
        $cur_topic['pnum'] = $pnum;

        if (date('d', $cur_topic['topic_posted']) == date('d') && $_SERVER['REQUEST_TIME'] - $cur_topic['topic_posted'] < 86400) {
            $cur_topic['topic_posted'] = rtrim((string)$lang['today_at']) . ' ' . date('H:i', $cur_topic['topic_posted']);
        } elseif (date('d', $cur_topic['topic_posted']) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $cur_topic['topic_posted'] < 2 * 86400) {
            $cur_topic['topic_posted'] = rtrim((string)$lang['yesterday_at']) . ' ' . date('H:i', $cur_topic['topic_posted']);
        } else {
            $cur_topic['topic_posted'] = date('M d, Y H:i', $cur_topic['topic_posted']);
        }

        if (date('d', $cur_topic['last_post']) == date('d') && $_SERVER['REQUEST_TIME'] - $cur_topic['last_post'] < 86400) {
            $cur_topic['last_post'] = rtrim((string)$lang['today_at']) . ' ' . date('H:i', $cur_topic['last_post']);
        } elseif (date('d', $cur_topic['last_post']) == date('d', $yesterday_ts) && $_SERVER['REQUEST_TIME'] - $cur_topic['last_post'] < 2 * 86400) {
            $cur_topic['last_post'] = rtrim((string)$lang['yesterday_at']) . ' ' . date('H:i', $cur_topic['last_post']);
        } else {
            $cur_topic['last_post'] = date('M d, Y H:i', $cur_topic['last_post']);
        }

        $cur_topic['linktothis'] = $MW->getConfig->temp->site_href . 'index.php?n=forum&sub=viewtopic&tid=' . $cur_topic['topic_id'];
        $cur_topic['linktolastpost'] = $MW->getConfig->temp->site_href . 'index.php?n=forum&sub=viewtopic&tid=' . $cur_topic['topic_id'] . '&to=lastpost';
        $cur_topic['linktoprofile1'] = $MW->getConfig->temp->site_href . 'index.php?n=account&sub=view&action=find&name=' . $cur_topic['username'];
        $cur_topic['linktoprofile2'] = $MW->getConfig->temp->site_href . 'index.php?n=account&sub=view&action=find&name=' . $cur_topic['last_poster'];
        $topics[] = $cur_topic;
    }

    return $topics;
}
