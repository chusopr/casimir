      <footer>
        <nav>
          <ul>
            <li><a href="<?php echo $casimir->base_url; ?>"><?php echo _("Home"); ?></a></li>
            <li><a href="<?php echo $casimir->base_url; ?>tools.php"><?php echo _('Tools'); ?></a></li>
            <li><a href="<?php echo $casimir->base_url; ?>stats.php#lastday"><?php echo _('Stats'); ?></a></li>
            <li class="poweredby"><a href="https://github.com/nhoizey/casimir#readme"><?php printf(_("Powered by %s"), "Cas.im/ir"); ?></a></li>
          </ul>
        </nav>
        <?php
        if (file_exists('user/footer.php')) {
          require 'user/footer.php';
        }
        ?>
      </footer>
    </div>
    <div style="float: left">
      <script type="text/javascript"><!--
        google_ad_client = "ca-pub-9329384101419370";
        google_ad_slot = "1345172944";
        google_ad_width = 160;
        google_ad_height = 600;
        //-->
      </script>
      <script type="text/javascript"
        src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
      </script>
    </div>
  </div>
</body>

</html>
