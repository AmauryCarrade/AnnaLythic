<?php header('Content-type: text/javascript'); ?>

console.log(window.navigator, window.parent.document);
alert('<?php echo $_GET['dump']; ?>');