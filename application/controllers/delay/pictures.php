<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author      OA Wu <comdan66@gmail.com>
 * @copyright   Copyright (c) 2015 OA Wu Design
 */
class Pictures extends Delay_controller {

  public function update_virtual_versions_color () {
    if (!(($id = OAInput::post ('id')) && ($picture = Picture::find_by_id ($id, array ('select' => 'id, name, color_r, color_g, color_b')))))
      return ;

    foreach ($picture->name->virtualVersions () as $key => $version)
      $picture->name->save_as ($key, $version);

    $picture->update_color ();
  }
}