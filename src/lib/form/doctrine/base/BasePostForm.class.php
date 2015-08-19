<?php

/**
 * Post form base class.
 *
 * @method Post getObject() Returns the current form's model object
 *
 * @package    musique-approximative
 * @subpackage form
 * @author     Tristan Rivoallan <tristan@rivoallan.net>
 * @version    SVN: $Id$
 */
abstract class BasePostForm extends BaseFormDoctrine
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'             => new sfWidgetFormInputHidden(),
      'body'           => new sfWidgetFormTextarea(),
      'track_title'    => new sfWidgetFormTextarea(),
      'track_author'   => new sfWidgetFormTextarea(),
      'track_filename' => new sfWidgetFormTextarea(),
      'track_md5'      => new sfWidgetFormInputText(),
      'buy_url'        => new sfWidgetFormInputText(),
      'svn_revision'   => new sfWidgetFormInputText(),
      'publish_on'     => new sfWidgetFormInputText(),
      'is_online'      => new sfWidgetFormInputCheckbox(),
      'contributor_id' => new sfWidgetFormDoctrineChoice(array('model' => $this->getRelatedModelName('sfGuardUser'), 'add_empty' => false)),
      'created_at'     => new sfWidgetFormDateTime(),
      'updated_at'     => new sfWidgetFormDateTime(),
      'slug'           => new sfWidgetFormInputText(),
    ));

    $this->setValidators(array(
      'id'             => new sfValidatorChoice(array('choices' => array($this->getObject()->get('id')), 'empty_value' => $this->getObject()->get('id'), 'required' => false)),
      'body'           => new sfValidatorString(),
      'track_title'    => new sfValidatorString(array('required' => false)),
      'track_author'   => new sfValidatorString(array('required' => false)),
      'track_filename' => new sfValidatorString(array('required' => false)),
      'track_md5'      => new sfValidatorString(array('max_length' => 32, 'required' => false)),
      'buy_url'        => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'svn_revision'   => new sfValidatorInteger(array('required' => false)),
      'publish_on'     => new sfValidatorPass(),
      'is_online'      => new sfValidatorBoolean(array('required' => false)),
      'contributor_id' => new sfValidatorDoctrineChoice(array('model' => $this->getRelatedModelName('sfGuardUser'), 'column' => 'id')),
      'created_at'     => new sfValidatorDateTime(),
      'updated_at'     => new sfValidatorDateTime(),
      'slug'           => new sfValidatorString(array('max_length' => 255, 'required' => false)),
    ));

    $this->validatorSchema->setPostValidator(
      new sfValidatorDoctrineUnique(array('model' => 'Post', 'column' => array('slug')))
    );

    $this->widgetSchema->setNameFormat('post[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    $this->setupInheritance();

    parent::setup();
  }

  public function getModelName()
  {
    return 'Post';
  }

}
