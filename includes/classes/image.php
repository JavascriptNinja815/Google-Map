<?php

/**
 * @author Joshua D. Burns <jdburnz@gmail.com>, +1 (616) 481-1585, <https://www.linkedin.com/in/joshuadburns>
 * @copyright Copyright (c) 2014, Joshua D. Burns. All Rights Reserved.
 * @license http://www.joshburns.me/licenses/CasterDepot License For Caster Depot (used to be named Great Lakes Caster)
 */

class Image {
	private $image;
	private $filename;
	private $width;
	private $height;

	public function __construct($filename_with_path) {
		$this->filename = $filename_with_path;
		$this->image = $this->loadImage();
	}

	/**
	 * @param type $save_as_filename Just filename, NOT full path. DO NOT include the file extension, .png will be added automatically. Will be saved to ~/resources/images/profile/[$save_as_filename].png
	 */
	public function saveProfilePicture($save_as_filename) { // Relative filename
		$filename_with_path = BASE_PATH . '/resources/images/profiles/' . $save_as_filename;
		$this->saveFile($filename_with_path . '.png', 1024, 1024, False); // Save full-size image
		$this->saveFile($filename_with_path . '-32.png', 32, 32, True);
		$this->saveFile($filename_with_path . '-48.png', 48, 48, True);
		$this->saveFile($filename_with_path . '-64.png', 64, 64, True);
		$this->saveFile($filename_with_path . '-128.png', 128, 128, True);
		$this->saveFile($filename_with_path . '-256.png', 256, 256, True);
	}

	/**
	 * @param type $save_as_filename Just filename, NOT full path. DO NOT include the file extension, .png will be added automatically. Will be saved to ~/resources/images/lists/[$save_as_filename].png
	 */
	public function saveListLogo($save_as_filename) { // Relative filename
		$filename_with_path = BASE_PATH . '/resources/images/lists/' . $save_as_filename;
		$this->saveFile($filename_with_path . '.png', 1024, 1024, False); // Save full-size image
		$this->saveFile($filename_with_path . '-32.png', 32, 32, False);
		$this->saveFile($filename_with_path . '-48.png', 48, 48, False);
		$this->saveFile($filename_with_path . '-64.png', 64, 64, False);
		$this->saveFile($filename_with_path . '-128.png', 128, 128, False);
		$this->saveFile($filename_with_path . '-256.png', 256, 256, False);
	}

	/**
	 * 
	 * @param type $filename_with_path
	 * @param type $width Optional.
	 * @param type $height Optional.
	 * @param type $crop_to_dimensions Optional. Crops image to the desired dimensions.
	 */
	public function saveFile($filename_with_path, $width, $height, $crop_to_dimensions = False) {
		$resizedImage = $this->resizeImage($width, $height, $crop_to_dimensions);
		imagePng($resizedImage, $filename_with_path, 9, PNG_ALL_FILTERS);
	}

	public function loadImage() {
		$image_info = getImageSize($this->filename); // http://www.php.net/manual/en/function.getimagesize.php
		$this->width = $image_info[0];
		$this->height = $image_info[1];

		// Check if there was an error calling getImageSize():
		if($image_info === False) {
			throw new Exception('Unable to grab image size');
			return;
		}

		// Define function names for possibly returned mime types
		$image_functions = array(
			IMAGETYPE_GIF  => 'imageCreateFromGif',
			IMAGETYPE_JPEG => 'imageCreateFromJpeg',
			IMAGETYPE_PNG  => 'imageCreateFromPng',
			IMAGETYPE_WBMP => 'imageCreateFromwBmp',
			IMAGETYPE_XBM  => 'imageCreateFromwXbm',
		);

		// Check if there was an error calling getImageSize():
		if(!function_exists($image_functions[$image_info[2]])) {
			throw new Exception('Unknown file type');
			return;
		}

		$image = $image_functions[$image_info[2]]($this->filename);
		imageAlphaBlending($image, False);
		imageSaveAlpha($image, True);

		return $image;
	}

	public function getFileExtension() {
		$file_extension = pathinfo($this->filename, PATHINFO_EXTENSION);
		return strtolower($file_extension);
	}
	
	function resizeImage($target_width, $target_height, $crop_to_dimensions = False) {
		//print 'Original: ' . $this->width . 'x' . $this->height . '<br />';

		if($crop_to_dimensions === True) {
			$width_ratio = $target_width / $this->width;
			//print 'Width Ratio: ' . $width_ratio . '<br />';
			$height_ratio = $target_height / $this->height;
			//print 'Height Ratio: ' . $height_ratio . '<br />';
			if($width_ratio > $height_ratio) {
				//print 'Width Wins<br />';
				$resized_width = $target_width;
				$resized_height = $this->height * $width_ratio;
			} else {
				//print 'Height Wins<br />';
				$resized_height = $target_height;
				$resized_width = $this->width * $height_ratio;
			}
		} else {
			$width_ratio = $target_width / $this->width;
			//print 'Width Ratio: ' . $width_ratio . '<br />';
			$resized_width = $target_width;
			$resized_height = $this->height * $width_ratio;
			//print 'Resized: ' . $resized_width . 'x' . $resized_height . '<br />';
			if($resized_height > $target_height) {
				$height_ratio = $target_height / $resized_height;
				$resized_height = $target_height;
				//print 'Height Ratio: ' . $height_ratio . '<br />';
				$resized_width = $resized_width * $height_ratio;
				//print 'Resized: ' . $resized_width . 'x' . $resized_height . '<br />';
			}
		}

		// Drop decimal values
		$resized_width = round($resized_width);
		$resized_height = round($resized_height);

		// Calculations for centering the image
		$offset_width = round(($target_width - $resized_width) / 2);
		$offset_height = round(($target_height - $resized_height) / 2);

		//print 'Width Offset: ' . $offset_width . '<br />';
		//print 'Height Offset: ' . $offset_height . '<br />';

		$new_image = imageCreateTrueColor($target_width, $target_height);
		imageAlphaBlending($new_image, False);
		imageSaveAlpha($new_image, True);
		$transparent = imageColorAllocateAlpha($new_image, 0, 0, 0, 127);
		imagefill($new_image, 0, 0, $transparent);

		imageCopyResampled($new_image, $this->image, $offset_width, $offset_height, 0, 0, $resized_width, $resized_height, $this->width, $this->height);

		//print 'Final: ' . $resized_width . 'x' . $resized_height . '<br /><br /><br />';
		return $new_image;
	}
	
	function resizeToHeight($original_width, $original_height, $desired_height) {
		//print 'resize height: ' . $desired_height . '<br />';
		$ratio = $desired_height / $original_height;
		//print '- ratio: ' . $ratio . '<br />';
		$width = $original_width * $ratio;
		//print '- new width: ' . $width . '<br />';
		//print '- new height: ' . $desired_height . '<br />';
		return array(
			'width' => $width,
			'height' => $desired_height
		);
	}

	function resizeToWidth($original_width, $original_height, $desired_width) {
		$ratio = $desired_width / $original_width;
		$height = $original_height * $ratio;
		return array(
			'width' => $desired_width,
			'height' => $height
		);
	}
}
