-- test_pvp_honor_data.sql
-- Run against: characters database (e.g. classiccharacters / tbccharacters / wotlkcharacters)
--
-- Populates honor/PvP fields on existing characters so the honor leaderboard
-- and PvP tracking pages have data to render.
--
-- NOTE: The MaNGOS server overwrites stored_* columns when a character logs out.
-- For stable test data, run this with the server stopped, or run it on characters
-- whose bots are not actively cycling (use the guid list approach below).
--
-- Targets up to 60 characters at level >= 1 (no level floor so more rows survive).
-- Values are deterministic — re-running gives the same results.
--
-- Fields set:
--   stored_honorable_kills    — total HKs  (page filter: must be > 0)
--   stored_dishonorable_kills — total DKs
--   stored_honor_rating       — total honor points (primary sort)
--   honor_highest_rank        — 1–14 (Classic PvP rank)
--
-- UNDO: reset everything back to zero
--   UPDATE characters SET stored_honorable_kills=0, stored_dishonorable_kills=0,
--          stored_honor_rating=0, honor_highest_rank=0 LIMIT 60;

SET @row := 0;

UPDATE characters c
JOIN (
    SELECT guid, (@row := @row + 1) AS rn
    FROM characters
    ORDER BY level DESC, guid ASC
    LIMIT 60
) ranked ON c.guid = ranked.guid
SET
    c.stored_honor_rating       = GREATEST(1200, 50000 - (ranked.rn * 820)),
    c.stored_honorable_kills    = GREATEST(50,   5800  - (ranked.rn * 94) + MOD(ranked.rn * 73, 400)),
    c.stored_dishonorable_kills = MOD(ranked.rn * 7, 18),
    c.honor_highest_rank        = GREATEST(1, 14 - FLOOR((ranked.rn - 1) / 4));
