<script language="php">
    Header("Content-type: image/png");
	if (file_exists ($file))
		$im = imageCreateFromJpeg($file);
	else $im = imageCreate(20,20);
	ImageJpeg($im);
	ImageDestroy($im);
</script>