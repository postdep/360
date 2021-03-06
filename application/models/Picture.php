<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 OA Wu Design
 */

class Picture extends OaModel {

  static $table_name = 'pictures';

  static $has_one = array (
  );

  static $has_many = array (
  );

  static $belongs_to = array (
  );

  private $next = '';
  private $prev = '';

  public function __construct ($attributes = array (), $guard_attributes = true, $instantiating_via_find = false, $new_record = true) {
    parent::__construct ($attributes, $guard_attributes, $instantiating_via_find, $new_record);

    OrmImageUploader::bind ('name', 'PictureNameImageUploader');
    OrmImageUploader::bind ('cover', 'PictureCoverImageUploader');
  }
  
  public function next ($is_admin = false) {
    if ($this->next !== '') return $this->next;
    
    if (!($next = Picture::find ('one', array ('select' => 'token', 'order' => 'id DESC', 'conditions' => $is_admin ? array ('id != ? AND id <= ?', $this->id, $this->id) : array ('id != ? AND id <= ? AND is_visibled = ?', $this->id, $this->id, 1)))))
      $next = Picture::find ('one', array ('select' => 'token', 'order' => 'id DESC', 'conditions' => $is_admin ? array ('id != ?', $this->id) : array ('id != ? AND is_visibled = ?', $this->id, 1)));

    return $this->next = $next;
  }
  public function prev ($is_admin = false) {
    if ($this->prev !== '') return $this->prev;

    if (!($prev = Picture::find ('one', array ('select' => 'token', 'order' => 'id ASC', 'conditions' => $is_admin ? array ('id != ? AND id >= ?', $this->id, $this->id) : array ('id != ? AND id >= ? AND is_visibled = ?', $this->id, $this->id, 1)))))
      $prev = Picture::find ('one', array ('select' => 'token', 'order' => 'id ASC', 'conditions' => $is_admin ? array ('id != ?', $this->id) : array ('id != ? AND is_visibled = ?', $this->id, 1)));

    return $this->prev = $prev;
  }
  public function destroy () {
    return $this->name->cleanAllFiles () && $this->cover->cleanAllFiles () && $this->delete ();
  }
  public function position () {
    return array (
        'x' => $this->x,
        'y' => $this->y,
        'z' => $this->z,
      );
  }
  public function color ($type = 'rgba', $alpha = 1) {
    if (!(isset ($this->color_r) && isset ($this->color_r) && isset ($this->color_g)))
      return '';

    $alpha = $alpha <= 1 ? $alpha >= 0 ? $alpha : 0 : 1;

    switch ($type) {
      default:
      case 'rgba':
        return 'rgba(' . $this->color_r . ', ' . $this->color_r . ', ' . $this->color_g . ', ' . $alpha . ')';
        break;
      case 'rgb':
        return 'rgb(' . $this->color_r . ', ' . $this->color_r . ', ' . $this->color_g . ')';
        break;
      case 'hex':
        return '#' . color_hex ($this->color_r) . '' . color_hex ($this->color_r) . '' . color_hex ($this->color_g);
        break;
    }
  }
  public function update_color ($image_utility = null) {
    if (!(isset ($this->id) && isset ($this->name) && isset ($this->color_r) && isset ($this->color_g) && isset ($this->color_b)))
      return false;

    if (!$image_utility)
      switch (Cfg::system ('orm_uploader', 'uploader', 'driver')) {
        case 'local':
          if (!file_exists ($fileName = FCPATH . implode ('/', $this->name->path ())))
            return false;

          $image_utility = ImageUtility::create ($fileName);
          break;

        case 's3':
          if (!(@S3::getObject (Cfg::system ('orm_uploader', 'uploader', 's3', 'bucket'), implode (DIRECTORY_SEPARATOR, $this->name->path ()), FCPATH . implode (DIRECTORY_SEPARATOR, $fileName = array_merge (Cfg::system ('orm_uploader', 'uploader', 'temp_directory'), array ((string)$this->name)))) && file_exists ($fileName = FCPATH . implode ('/', $fileName))))
            return false;
          $image_utility = ImageUtility::create ($fileName);
          break;

        default:
          return false;
          break;
      }

    if (!(($analysis_datas = $image_utility->resize (10, 10, 'w')->getAnalysisDatas (1)) && isset ($analysis_datas[0]['color']) && ($analysis_datas = $analysis_datas[0]['color']) && (isset ($analysis_datas['r']) && isset ($analysis_datas['g']) && isset ($analysis_datas['b']))))
      return false;

    $average = 128;

    $red = round ($analysis_datas['r'] / 10) * 10;
    $green = round ($analysis_datas['g'] / 10) * 10;
    $blue = round ($analysis_datas['b'] / 10) * 10;

    $red += (round (($red - $average) / 10) * 1.125) * 10;
    $green += (round (($green - $average) / 10) * 1.125) * 10;
    $blue += (round (($blue - $average) / 10) * 1.125) * 10;

    $red = round ($red > 0 ? $red < 256 ? $red : 255 : 0);
    $green = round ($green > 0 ? $green < 256 ? $green : 255 : 0);
    $blue = round ($blue > 0 ? $blue < 256 ? $blue : 255 : 0);
    
    $this->color_r = max (0, min ($red, 255));
    $this->color_g = max (0, min ($green, 255));
    $this->color_b = max (0, min ($blue, 255));

    if (in_array (Cfg::system ('orm_uploader', 'uploader', 'driver'), array ('s3')))
      @unlink ($fileName);

    return $this->save ();
  }
  public function location () {
    if (!(isset ($this->latitude) && isset ($this->longitude) && ($this->latitude != -1) && ($this->longitude != -1)))
      return false;
    return true;
  }
}