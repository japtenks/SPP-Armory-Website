<div class="main-content">
  <style>
    .main-content {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-top: 20px;
    }
    .banner-top {
      width: 470px;
      margin: 0 auto 20px auto;
    }
    .banner-top img {
      display: block;
      width: 100%;
      height: auto;
    }
    .news-container {
      width: 470px;
      background: #000;
      padding: 0;
    }
    .news-archive-link {
      text-align: center;
      margin-top: 10px;
    }
  </style>

  <div class="banner-top">
    <?php if ((int)$MW->getConfig->generic->display_banner_flash): ?>
      <embed type="application/x-shockwave-flash"
             src="./flash/loader2.swf"
             id="flashbanner"
             name="flashbanner"
             quality="high"
             wmode="transparent"
             base="./flash/<?php echo $GLOBALS['user_cur_lang']; ?>"
             flashvars="xmlname=news.xmls"
             height="340"
             width="470" />
    <?php else: ?>
      <img src="<?php echo $currtmp; ?>/images/banner_top.png" alt="News Banner" />
    <?php endif; ?>
  </div>

  <div class="news-container">
    <?php foreach($alltopics as $postnum => $topic){ $postnum++; $hl = ($hl=='alt') ? '' : 'alt'; ?>
      <div class="news-expand" id="news<?php echo $topic['topic_id'];?>">
        <div class="news-listing">
          <div class="hoverContainer" onclick="toggleEntry('<?php echo $topic['topic_id'];?>','<?php echo$hl;?>')">
            <div class="news-top">
              <ul>
                <li class="item-icon">
                  <img src="<?php echo $currtmp; ?>/images/news-contests.gif" alt="icon" />
                </li>
                <li class="news-entry">
                  <h1><?php echo $topic['topic_name'];?></h1>
                  <span class="user">Posted by: <b><?php echo $topic['topic_poster'];?></b> | <?php echo date('d-m-Y',$topic['topic_posted']);?></span>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <div class="news-item">
          <blockquote>
            <div class="blog-post">
              <?php echo $topic['message'];?>
              <div align="right">
                <a href="<?php echo mw_url('forum', 'viewtopic', ['tid'=>$topic['topic_id'],'to'=>'lastpost']);?>"><?php echo $lang['lastcomment'];?></a>
                <?php echo $lang['from'];?> <a href="<?php echo mw_url('account','view',['action'=>'find','name'=>$topic['last_poster']]);?>"><?php echo $topic['last_poster']; ?></a>
              </div>
            </div>
          </blockquote>
        </div>
      </div>
    <?php } ?>
  </div>


</div>
