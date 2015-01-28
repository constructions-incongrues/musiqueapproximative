<?php
class maValidatedFile extends sfValidatedFile
{
  public function generateFilename()
  {
  	$filename = str_replace("'", ' ', $this->getOriginalName());
  	$filename = str_replace("\\", ' ', $filename);
  	$filename = str_replace('&', '-', $filename);
  	return filter_var($filename, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
  }
}
