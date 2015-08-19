<?php
class postComponents extends sfComponents
{
  public function executeContributors(sfWebRequest $request)
  {
    $contributors = array();

    // TODO : this is crappy performance-wise
    $contributors = Doctrine_Core::getTable('sfGuardUser')
    	->createQuery('u')
    	->innerJoin('u.UserProfile p')
    	->where('u.is_active = 1')
    	->orderBy('p.display_name')
    	->execute();

    // Pass data to view
    $this->contributors = $contributors;
  }
}
