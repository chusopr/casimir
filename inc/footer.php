<footer>
      <nav>
        <ul>
          <li><a href="<?php echo $casimir->base_url; ?>"><?php echo _("Home"); ?></a></li>
          <li><a href="<?php echo $casimir->base_url; ?>tools.php"><?php echo _('Tools'); ?></a></li>
          <li><a href="<?php echo $casimir->base_url; ?>stats.php#lastday"><?php echo _('Stats'); ?></a></li>
          <li class="poweredby"><a href="https://github.com/chusopr/casimir#readme"><?php printf(_("Powered by %s"), "Cas.im/ir"); ?></a></li>
        </ul>
      </nav>
      <?php
      if (file_exists('user/footer.php')) {
        require 'user/footer.php';
      }
      ?>
    </footer>
  </div>
</body>

</html>
