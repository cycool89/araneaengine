<?php
namespace aecore;

/**
 * Alkalmazás modulnak implementálandó metódusai.
 * 
 * @author Kigyós János <cycool89@gmail.com>
 */
interface IApplication {

  /**
   * Az alkalmazás modulban a boot() metódus után fut le.
   * Javaslat: itt implementáljuk az alkalmazás logikát.
   */
  function index();

  /**
   * Az alkalmazás modulban az index() metódus után fut le.
   * Javaslat: itt implementáljuk az alkalmazás megjelenítést.
   */
  function render();
  
  /**
   * Controllerek metódusait kívülről meghívva lefut mielőtt végrehejtódna a metódus.
   * true visszatérés esetén hajtja végre a Controller metódus-t.
   * Más esetben nem hajtja végre.
   * 
   * @param string $class végrehajtandó controller osztály neve.
   * @param string $method végrehajtandó controller osztály metódusa.
   * @return boolean
   */
  function beforeCall($class, $method);

  /**
   * Controllerek metódusait kívülről meghívva lefut miután végrehajtódott a metódus.
   * Ehhez kell, hogy a beforeCall() true értékkel térjen vissza.
   * 
   * @param string $class végrehajtandó controller osztály neve.
   * @param string $method végrehajtandó controller osztály metódusa.
   */
  function afterCall($class, $method);
}
