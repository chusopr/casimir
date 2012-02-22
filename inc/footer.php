<footer>
      <nav>
        <ul>
          <li><a href="<?php echo $casimir->base_url; ?>">Home</a></li>
          <li><a href="<?php echo $casimir->base_url; ?>tools.php">Tools</a></li>
          <li><a href="<?php echo $casimir->base_url; ?>stats.php#lastday">Stats</a></li>
          <li class="poweredby"><a href="https://github.com/nhoizey/casimir#readme">Powered by Cas.im/ir</a></li>
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
