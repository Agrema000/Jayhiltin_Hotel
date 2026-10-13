<?php
// Redirect root traffic to the guest index page
header("Location: guest/index.php", true, 301);
exit();