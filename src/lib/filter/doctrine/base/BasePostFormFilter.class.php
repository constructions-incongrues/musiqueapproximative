<?php

/**
 * Post filter form base class.
 *
 * @package    musique-approximative
 * @subpackage filter
 * @author     Tristan Rivoallan <tristan@rivoallan.net>
 * @version    SVN: $Id$
 */
abstract class BasePostFormFilter extends BaseFormFilterDoctrine
{
  public function setup()
  {
    $this->setWidgets(array(
      'body'           => new sfWidgetFormFilterInput(array('with_empty' => false)),
      'track_title'    => new sfWidgetFormFilterInput(),
      'track_author'   => new sfWidgetFormFilterInput(),
      'track_filename' => new sfWidgetFormFilterInput(),
      'track_md5'      => new sfWidgetFormFilterInput(),
      'buy_url'        => new sfWidgetFormFilterInput(),
      'svn_revision'   => new sfWidgetFormFilterInput(),
      'publish_on'     => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
      'is_online'      => new sfWidgetFormChoice(array('choices' => array('' => 'yes or no', 1 => 'yes', 0 => 'no'))),
      'contributor_id' => new sfWidgetFormDoctrineChoice(array('model' => $this->getRelatedModelName('sfGuardUser'), 'add_empty' => true)),
      'created_at'     => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
      'updated_at'     => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
      'slug'           => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'body'           => new sfValidatorPass(array('required' => false)),
      'track_title'    => new sfValidatorPass(array('required' => false)),
      'track_author'   => new sfValidatorPass(array('required' => false)),
      'track_filename' => new sfValidatorPass(array('required' => false)),
      'track_md5'      => new sfValidatorPass(array('required' => false)),
      'buy_url'        => new sfValidatorPass(array('required' => false)),
      'svn_revision'   => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'publish_on'     => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDateTime(array('required' => false, 'datetime_output' => 'Y-m-d 00:00:00')), 'to_date' => new sfValidatorDateTime(array('required' => false, 'datetime_output' => 'Y-m-d 23:59:59')))),
      'is_online'      => new sfValidatorChoice(array('required' => false, 'choices' => array('', 1, 0))),
      'contributor_id' => new sfValidatorDoctrineChoice(array('required' => false, 'model' => $this->getRelatedModelName('sfGuardUser'), 'column' => 'id')),
      'created_at'     => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDateTime(array('required' => false, 'datetime_output' => 'Y-m-d 00:00:00')), 'to_date' => new sfValidatorDateTime(array('required' => false, 'datetime_output' => 'Y-m-d 23:59:59')))),
      'updated_at'     => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDateTime(array('required' => false, 'datetime_output' => 'Y-m-d 00:00:00')), 'to_date' => new sfValidatorDateTime(array('required' => false, 'datetime_output' => 'Y-m-d 23:59:59')))),
      'slug'           => new sfValidatorPass(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('post_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    $this->setupInheritance();

    parent::setup();
  }

  public function getModelName()
  {
    return 'Post';
  }

  public function getFields()
  {
    return array(
      'id'             => 'Number',
      'body'           => 'Text',
      'track_title'    => 'Text',
      'track_author'   => 'Text',
      'track_filename' => 'Text',
      'track_md5'      => 'Text',
      'buy_url'        => 'Text',
      'svn_revision'   => 'Number',
      'publish_on'     => 'Date',
      'is_online'      => 'Boolean',
      'contributor_id' => 'ForeignKey',
      'created_at'     => 'Date',
      'updated_at'     => 'Date',
      'slug'           => 'Text',
    );
  }
}
