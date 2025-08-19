<?php
// Traditional login has been disabled - redirect to Steam authentication
header('Location: ../steamauth/steamauth.php?login');
exit;
?>
