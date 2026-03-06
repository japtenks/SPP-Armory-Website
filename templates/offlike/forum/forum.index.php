<style>
/* ---------- Category Blocks ---------- */
.forum-category {
  margin-bottom: 18px;
  border-radius: 6px;
  background: #161616;
  box-shadow: inset 0 0 6px rgba(0,0,0,0.7);
  padding: 10px;
}

.modern-title {
  background: linear-gradient(to right, #604015, #3a2a10);
  color: #ffcc66;
  font-size: 1.1rem;
  font-weight: bold;
  padding: 8px 10px;
  border-radius: 4px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ---------- Forum Entry ---------- */
.forum-entry {
  display: flex;
  align-items: flex-start;
  border-bottom: 1px solid #222;
  padding: 10px 6px;
  transition: background 0.25s;
}

.forum-entry:hover {
  background: rgba(255, 204, 102, 0.08);
}

/* Icon */
.forum-icon img {
  width: 28px;
  height: 28px;
  margin-right: 10px;
}

/* Details */
.forum-details {
  flex: 1;
  min-width: 0;
}

.forum-title {
  font-weight: bold;
  color: #b0d0ff;
  text-decoration: none;
  transition: color 0.2s, text-shadow 0.3s;
}

.forum-title:hover {
  color: #ffd97a;
  text-shadow: 0 0 8px rgba(255,204,102,0.4);
}

.forum-desc {
  color: #aaa;
  font-size: 0.9rem;
  margin: 4px 0;
}

.lastreply {
  color: #888;
  font-size: 0.85rem;
}

/* Stats */
.forum-stats {
  text-align: right;
  color: #ccc;
  min-width: 100px;
  font-size: 0.85rem;
  line-height: 1.2;
}

/* Legend */
.forum-legend {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  font-size: 0.85rem;
  color: #bbb;
  padding: 12px 0 4px;
  border-top: 1px solid #222;
  margin-top: 10px;
}

.forum-legend img {
  vertical-align: middle;
  width: 18px;
  height: 18px;
  margin-right: 6px;
}

/* Responsive */
@media (max-width: 700px) {
  .forum-entry {
    flex-direction: column;
    align-items: flex-start;
  }
  .forum-stats {
    text-align: left;
    margin-top: 4px;
  }
}

/* Centered Header Image */
img[src*="forum_top.png"] {
  display: block;
  margin: 0 auto 12px auto;
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
}

</style>
<?php
// ========================================================
// Load forum categories + forums
// ========================================================
$categories = $DB->select("
    SELECT cat_id, cat_name
    FROM tbcrealmd.f_categories
    ORDER BY cat_disp_position ASC
");

$items = [];

foreach ($categories as $cat) {
    $forums = $DB->select("
        SELECT f.*,
               t.topic_name,
               t.last_post,
               t.last_poster
        FROM tbcrealmd.f_forums AS f
        LEFT JOIN tbcrealmd.f_topics AS t ON f.last_topic_id = t.topic_id
        WHERE f.cat_id = ?d
        ORDER BY f.disp_position ASC
    ", $cat['cat_id']);

    if ($forums) {
        foreach ($forums as &$forum) {
            $forum['cat_name'] = $cat['cat_name'];
            $forum['linktothis'] = "index.php?n=forum&sub=viewforum&fid=" . (int)$forum['forum_id'];
            $forum['linktolastpost'] = !empty($forum['last_topic_id'])
                ? "index.php?n=forum&sub=viewtopic&tid=" . (int)$forum['last_topic_id']
                : "#";
            $forum['topic_name'] = $forum['topic_name'] ?? '';
            $forum['last_poster'] = $forum['last_poster'] ?? '';
            $forum['last_post'] = !empty($forum['last_post'])
                ? date('d-m-Y, H:i', $forum['last_post'])
                : '';
        }
        $items[] = $forums;
    }
}

// ========================================================
// Render Forum Index
// ========================================================
if (true):
  builddiv_start(1, $lang['spp_forum']);
?>
<div class="modern-content">
  <img src="<?php echo $currtmp; ?>/images/forum_top.png" alt="Forums" class="forum-header"/>

  <div class="modern-content forum-container">
    <?php if (empty($items)): ?>
      <div class="forum-empty">No forums available.</div>
    <?php endif; ?>

    <?php foreach ($items as $catitem): ?>
      <section class="forum-category modern-block">
        <div class="modern-title">
          <img src="<?php echo $currtmp; ?>/images/nav_m.gif" alt="Potatoes"/> 
          <?php echo htmlspecialchars($catitem[0]['cat_name']); ?>
        </div>

        <?php foreach ($catitem as $forumitem): ?>
          <article class="forum-entry">
            <div class="forum-icon">
              <img src="<?php echo $currtmp; ?>/images/<?php
                echo $forumitem['closed']
                  ? 'lock-icon.gif'
                  : ($forumitem['isnew'] ?? false
                    ? 'news-community.gif'
                    : 'no-news-community.gif');
              ?>" alt="Lord farquad"/>
            </div>

            <div class="forum-details">
              <a class="forum-title" href="<?php echo $forumitem['linktothis']; ?>">
                <?php echo htmlspecialchars($forumitem['forum_name']); ?>
              </a>
              <p class="forum-desc"><?php echo htmlspecialchars($forumitem['forum_desc']); ?></p>

              <?php if (!empty($forumitem['topic_name'])): ?>
                <div class="lastreply">
                  <?php echo $lang['lastreplyin']; ?> 
                  <a href="<?php echo $forumitem['linktolastpost']; ?>">
                    <?php echo htmlspecialchars($forumitem['topic_name']); ?>
                  </a><br/>
                  <?php echo $lang['from']; ?> 
                  <span><?php echo htmlspecialchars($forumitem['last_poster']); ?></span> 
                  <?php echo $forumitem['last_post']; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="forum-stats">
              <div><?php echo (int)$forumitem['num_topics']; ?> <?php echo $lang['l_theme2']; ?></div>
              <div><?php echo (int)$forumitem['num_posts']; ?> <?php echo $lang['l_post2']; ?></div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>

    <div class="forum-legend">
      <div><img src="<?php echo $currtmp; ?>/images/news-community.gif" alt=""/> <?php echo $lang['newpost']; ?></div>
      <div><img src="<?php echo $currtmp; ?>/images/no-news-community.gif" alt=""/> <?php echo $lang['nonewpost']; ?></div>
      <div><img src="<?php echo $currtmp; ?>/images/lock-icon.gif" alt=""/> <?php echo $lang['postclose']; ?></div>
    </div>
  </div>
</div>
<?php builddiv_end(); ?>
<?php endif; ?>
