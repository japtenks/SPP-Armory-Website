
<style>

/* ---------- News Polished WoW Style ---------- */

.news-container {
  width: 80%;
  margin: 18px auto;
  

  padding: 22px 26px;
  font-family: "Trebuchet MS", Verdana, Arial, sans-serif;
  backdrop-filter: blur(2px);
}

/* article spacing */
.news-expand {
  margin-bottom: 20px;
  border-bottom: 1px solid #1e1e1e;
  padding-bottom: 12px;
}

/* header clickable area */
.news-listing {
  cursor: pointer;
  padding: 10px;
  border-radius: 6px;
  transition: background 0.25s ease, transform 0.1s ease;
}
.news-listing:hover {
  background: rgba(210,180,90,0.14);
  transform: translateY(-1px);
}

/* header row layout */
.news-top ul {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.news-top .item-icon img {
  width: 42px;
  height: 42px;
  border-radius: 4px;
  border: 1px solid #292015;
  background: #000;
  box-shadow: 0 0 6px rgba(0,0,0,0.6);
}

.news-entry h1 {
  background: linear-gradient(to right, rgba(30, 20, 5, 0.85), rgba(12, 10, 6, 0.6));
  padding: 8px 12px;
  border-radius: 6px;
  border: 1px solid rgba(180,140,60,0.3);
  box-shadow: inset 0 0 10px rgba(255,200,90,0.08);
  color: #ffcb66;
  margin: 0 0 6px;
  font-weight: 700;
  font-size: 1.15rem;
  text-shadow: 0 0 6px rgba(0,0,0,0.8);
}
.news-expand {
  margin-bottom: 20px;
  border: 1px solid rgba(50, 40, 20, 0.4);
  border-radius: 8px;
  background: rgba(0,0,0,0.4);
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
  padding: 16px;
  transition: transform 0.15s ease, box-shadow 0.25s ease;
}
.news-expand:hover {
  transform: translateY(-2px);
  box-shadow: 0 0 18px rgba(255,215,80,0.15);
}


.news-entry .user {
  font-size: 0.82rem;
  color: rgba(200,180,120,0.8);
  text-shadow: 0 0 2px rgba(0,0,0,0.8);
}


/* content block */
.news-item blockquote {
  margin: 0;
  padding: 12px 14px;
  border-left: 3px solid #bda66c;
  background: #101010;
  border-radius: 6px;
  box-shadow: inset 0 0 8px rgba(0,0,0,0.7);
}

.blog-post ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.blog-post li {
  position: relative;
  margin: 6px 0;
  padding-left: 16px;
}
.blog-post li::before {
  content: "•";
  color: #ffcc66;
  position: absolute;
  left: 0;
  top: 0;
}


/* banner consistency */
.modern-content img[alt*="Banner"] {
  display: block;
  width: 100%;
  max-width: 520px;
  height: auto;
  margin: 0 auto 20px;
   margin-top: 30px;
  border-radius: 8px;
  box-shadow: 0 0 16px rgba(0,0,0,0.7);
  border: 1px solid #3a2b18;
}

/* section header */
.builddiv-header, .modern-content h2 {
  color: #f8c46c;
  font-size: 1.2rem;
  font-weight: bold;
  text-align: center;
  text-shadow: 0 0 8px rgba(255,215,128,0.5);
  margin-bottom: 12px;
}




</style>


<?php builddiv_start(1, $lang['forum_news']); ?>
<div class="modern-content">
 <img src="<?php echo $currtmp; ?>/images/banner_top.png" alt="News Banner" />
    

  <!-- News Container -->
  <div class="news-container">
    <?php 
    if (!empty($alltopics)):
      $hl = '';
      foreach ($alltopics as $postnum => $topic): 
        $postnum++;
        $hl = ($hl == 'alt') ? '' : 'alt';
    ?>
      <div class="news-expand" id="news<?php echo $topic['topic_id']; ?>">
        <div class="news-listing" onclick="toggleEntry('<?php echo $topic['topic_id']; ?>','<?php echo $hl; ?>')">
          <div class="news-top">
            <ul>
              <li class="item-icon">
                <img src="<?php echo $currtmp; ?>/images/news-contests.gif" alt="icon" />
              </li>
              <li class="news-entry">
                <h1><?php echo htmlspecialchars($topic['topic_name']); ?></h1>
                <span class="user">
                  Posted by: <b><?php echo htmlspecialchars($topic['topic_poster']); ?></b> |
                  <?php echo date('d-m-Y', $topic['topic_posted']); ?>
                </span>
              </li>
            </ul>
          </div>
        </div>

        <div class="news-item">
          <blockquote>
            <div class="blog-post">
              <?php echo $topic['rendered_message'] ?? ''; ?>
              <div style="text-align:right;margin-top:6px;">
                <a href="<?php echo mw_url('forum', 'viewtopic', ['tid'=>$topic['topic_id'],'to'=>'lastpost']);?>">
                  <?php echo $lang['lastcomment'];?>
                </a>
                <?php echo $lang['from']; ?>
                <a href="<?php echo mw_url('account','view',['action'=>'find','name'=>$topic['last_poster']]);?>">
                  <?php echo htmlspecialchars($topic['last_poster']); ?>
                </a>
              </div>
            </div>
          </blockquote>
        </div>
      </div>
    <?php endforeach; endif; ?>
  </div>

</div> <!-- closes .modern-content -->
<?php builddiv_end(); ?>


<script>
  // Placeholder: can re-enable collapsible posts here later
  function toggleEntry(id, hl) {
    const el = document.getElementById('news' + id);
    if (el) el.classList.toggle('collapsed');
  }
</script>
