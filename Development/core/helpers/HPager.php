<?php
namespace aecore;
/**
 * Description of CMXPager
 *
 * @author cycool89
 */
class HPager
{
  private $view = null;
  private $url = 'oldal';
  private $actPage = 1;
  private $itemsPerPage = 10;
  private $next;
  private $prev;
  private $last;
  private $first;
  
  function __construct()
  {
    $this->view = AE()->getApplication()->getView();
  }
  
  function setUrlText($text)
  {
    $this->url = $text;
  }
  
  function setItemsPerPage($num)
  {
    $this->itemsPerPage = $num;
  }
  
  private function _genViewVariables()
  {
    $this->view->assign('pager_prev',$this->prev);
    $this->view->assign('pager_next',$this->next);
    $this->view->assign('pager_act',$this->actPage);
    $this->view->assign('pager_url',$this->url);
    $this->view->assign('pager_first',$this->first);
    $this->view->assign('pager_last',$this->last);
  }
  
  function generateDatas($datas)
  {
    $ret = array();
    $this->first = 1;
    $this->last = ceil(count($datas) / $this->itemsPerPage);
    
    $this->actPage = (param($this->url) !== false) ? Request::Params(Request::Params($this->url) + 1) : 1;
    $this->actPage = ($this->actPage > $this->last) ? $this->last : $this->actPage;
    $this->actPage = ($this->actPage < $this->first) ? $this->first : $this->actPage;
    
    $this->prev = ($this->actPage <= 1)? 1 : $this->actPage - 1;
    $this->next = ($this->actPage >= $this->last)? $this->last : $this->actPage + 1;
    $i = ($this->actPage-1) * $this->itemsPerPage;
    while ($i < count($datas) && $i < ((($this->actPage-1) * $this->itemsPerPage) + ($this->itemsPerPage)))
    {
      $ret[] = $datas[$i];
      $i++;
    }
    $this->_genViewVariables();
    return $ret;
  }
}
