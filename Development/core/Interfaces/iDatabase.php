<?php
namespace core;
/**
 * AraneaEngine adatbázis interface
 * 
 * 2014.11.22
 * @author Kigyós János <cycool89@gmail.com>
 */
interface iDatabase {

  function connect($host, $username, $password, $dbname);
  
  /**
   * Közvetlen sql(<var>$query</var>) lekérdezés
   * Visszatérési érték mysqli objektum
   * 
   * @param type string $query
   * @return type mixed
   */
  function execute($query);

  /**
   * SQL lekérdezés opcionális limit megadással.
   * A fv az előzőleg meghívott fv-ekkel együtt dolgozva hozzáfűzi a <var>$query</var> -hez a lekérés többi részét.
   * Visszatérési érték mysqli objektum
   * 
   * @param string $query
   * @param integer $offset
   * @param integer $limit
   * @return mixed
   */
  function query($query, $offset = false, $limit = false);

  /**
   * Azonos a query fv-el
   * Visszatérési érték a lekérdezés első sor, első oszlopának értéke
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getOne($query);

  /**
   * Azonos a query fv-el
   * Visszatérési érték a lekérdezés első sora asszociatív tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getRow($query);

  /**
   * Azonos a query fv-el
   * Visszatérési értéke az eredmény halmaz sorszámotott tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getArray($query);

  /**
   * Azonos a query fv-el
   * Visszatérési értéke az eredmény halmaz minden sor, első oszlopának értéke sorszámotott tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @return mixed
   */
  function getArrayOne($query);

  /**
   * Azonos a query fv-el
   * Visszatérési értéke az eredmény halmaz <var>$key</var> indexű tömbben
   * vagy false sikertelenség esetén
   * 
   * @param string $query
   * @param string $key
   * @return mixed
   */
  function getKeyedArray($query, $key);

  /**
   * Előkészítő fv, amivel a későbbi lekéréshez lehet beállítani limitet.
   * 
   * @param integer $offset
   * @param integer $limit
   */
  function addLimit($offset = false, $limit = false);

  /**
   * Előkészítő fv, amivel a későbbi lekéréshez lehet beállítani rendezést.
   * <var>$field</var> a mező neve ami szerint rendezünk.
   * [<var>$mode</var> = ASC,DESC növekvő, csökkenő sorrend.]
   * Többször meghívva lehet több mező szerint rendezni.
   * 
   * @param string $field
   */
  function orderBy($field, $mode = 'ASC');

  /**
   * Oszlop (<var>$field</var>) hozzáadása <var>$table</var> táblához
   * <var>$props</var> tulajdonságokkal
   * 
   * <var>$props</var> szintaxisa megegyezik a MYSQL szintaxissal pl.:
   *    'INT(11)'
   * NOT NULL tulajdonság alapértelmezetten hozzáadódik.
   * 
   * Visszatérési érték siker esetén true, egyébként false
   * 
   * @param string $table
   * @param string $field
   * @param string $props
   * @return boolean
   */
  function addColumn($table, $field, $props);

  /**
   * Oszlop (<var>$field</var>) törlése <var>$table</var> táblából
   * 
   * Visszatérési érték siker esetén true, egyébként false
   * 
   * @param string $table
   * @param string $field
   * @return boolean
   */
  function dropColumn($table, $field);

  /**
   * Oszlop (<var>$field</var>) módosítása <var>$table</var> táblában
   * <var>$props</var> tulajdonságokra
   * 
   * <var>$props</var> szintaxisa megegyezik a MYSQL szintaxissal pl.:
   *    'INT(11)'
   * NOT NULL tulajdonság alapértelmezetten hozzáadódik.
   * 
   * Visszatérési érték siker esetén true, egyébként false
   * 
   * @param string $table
   * @param string $field
   * @param string $props
   * @return boolean
   */
  function modifyColumn($table, $field, $props);

  /**
   * Befejező utasítás
   * SQL SELECT utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblából
   * <var>$fields</var> mezőket kérdezi le, ahol
   * <var>$fields</var> lehet string illetve az oszlopneveket tartalmazó tömb.
   * 
   * @param mixed $fields
   * @param string $tableName
   * @return MySQL result Object
   */
  public function select($fields, $tableName);

  /**
   * Befejező utasítás
   * SQL INSERT utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblába
   * <var>$insertData</var> [és <var>$insertData2</var>] adatokat szúrja be, ahol
   * <var>$insertData</var> legyen mezőnév(kulcs) => érték párosításban összeállított tömb
   *  vagy
   * <var>$insertData</var>  legyen mezőneveket tartalmazó sorszámozott tömb és
   * <var>$insertData2</var> legyen értékeket tartalmazó sorszámozott tömb
   * 
   * @param string $tableName
   * @param array $insertData
   * @param array $insertData2
   * @return boolean
   */
  public function insert($tableName, $insertData = array(), $insertData2 = false);

  /**
   * Befejező utasítás
   * SQL UPDATE utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblában
   * <var>$tableData</var> [és <var>$tableData2</var>] adatokat UPDATE-li, ahol
   * <var>$tableData</var> legyen mezőnév(kulcs) => érték párosításban összeállított tömb
   *  vagy
   * <var>$tableData</var>  legyen mezőneveket tartalmazó sorszámozott tömb és
   * <var>$tableData2</var> legyen értékeket tartalmazó sorszámozott tömb
   * 
   * @param string $tableName
   * @param array $insertData
   * @param array $insertData2
   * @return boolean
   */
  public function update($tableName, $tableData, $tableData2 = false);

  /**
   * Befejező utasítás
   * SQL DELETE utasítást állít össze és futtat le az előkészítő fv-ekkel összedolgozva.
   * A lekérdezés <var>$tableName</var> táblából törli az addWhere fv feltételekkel megadott eleme(ke)t
   * 
   * @param string $tableName
   * @return boolean
   */
  public function delete($tableName);

  /**
   * Előkészítő fv
   * WHERE feltételt fűz a később lekérdezéshez a következő módon:
   * <var>$whereProp</var> mezőnév <var>$rel</var>('=','<','>') <var>$whereValue</var> érték
   * 
   * Különböző fv hívások AND-el lesznek összefűzve
   * 
   * @param type string $whereProp
   * @param type mixed $whereValue
   */
  public function addWhere($whereProp, $whereValue, $rel = '=');

  public function getQueryString();

  public function affected_rows();

  public function last_insert_id();
}
