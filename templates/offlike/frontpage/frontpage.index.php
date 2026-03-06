



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
              <?php echo $topic['message']; ?>
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
