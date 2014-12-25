<?php
namespace aecore;
/**
 * @property CMXDB $db
 */
class HTree
{

    static public $instance = false;
    protected $id = '';
    protected $left = '';
    protected $right = '';
    protected $level = '';
    protected $pos = '';
    protected $pid = '';
    protected $db = '';
    protected $tname = '';
    protected $pref = '';
    protected $ownColumnsName = '';
    protected $root_id = '';

    function __construct($tname, $pref = '')
    {
        $this->db = AE()->getDatabase();
        $app = AE()->getApplication();

        $this->tname = $tname;

        $increment = FALSE;
        if ($pref == '')
        {
            $this->pref = $tname . '_';
        } else
        {
            $this->pref = $pref;
        }
        $this->id = $this->pref . 'id';
        $this->left = $this->pref . 'left';
        $this->right = $this->pref . 'right';
        $this->level = $this->pref . 'level';
        $this->pos = $this->pref . 'pos';
        $this->pid = $this->pref . 'pid';
        /*
         * A táblanevek lekérdezése
         */
        $q = sprintf('SHOW FULL COLUMNS FROM %1$s', $this->tname);

        if (!$result = $this->db->query($q))
        {
            /*
             * ha nincs ilyen tábla, kilép
             */
            Log::write('Nincs ilyen tábla = ' . $this->tname, true);
            return false;
        }

        while ($row = $result->fetch_assoc())
        {
            /*
             * Ha a mező autoincrement, ez lesz a $this->id
             */
            if ($row['Extra'] == 'auto_increment')
            {
                $this->id = $row['Field'];
                $increment = TRUE;
            } else
            {
                /*
                 * ha a mező lehet Null, kilép
                 */
                if ($row['Null'] != 'NO')
                {
                    Log::write('A ' . $row[Field] . ' mező nem jó, mert lehet NULL', true);
                    return false;
                }
            }
            $delColumns[] = $row['Field'];
        }
        /*
         * ha minden meglévő mező szerepel a kötelező mezőnevek között
         */
        $this->ownColumnsName = array(
            $this->id,
            $this->left,
            $this->right,
            $this->level,
            $this->pos,
            $this->pid,
        );
        if (@!is_array($delColumns))
        {
            $delColumns = array();
        }
        /*
         * megnézi, milyen kötelező mezőket kell hozzáadni a táblához
         */
        $addColumns = array_diff($this->ownColumnsName, $delColumns);

        if ($increment)
        {
            /*
             * ha volt autoincrement mező
             */
            foreach ($addColumns as $key => $value)
            {
                /*
                 * megkeresi, és törli a hozzáadand mezők közül
                 */
                if ($value == $pref . 'id')
                    unset($addColumns[$key]);
            }
        }
        /*
         * módosítja a táblát
         */
        foreach ($addColumns as $key => $colname)
        {
            /*
             * az oszlop neve $colname
             */


            if ($colname == $this->id)
            {
                /*
                 * ha a mező id, akkor autoincrement
                 */
                $coldef = 'INT(11) AUTO_INCREMENT';
            } else
            {
                /*
                 * ha nem id, akkor integer
                 */
                $coldef = 'INT(11)';
            }
            /*
             * végrehajtja a lekérdezést
             */

            $this->db->addColumn($this->tname, $colname, $coldef);
        }
        /*
         * van-e adat a táblázatban
         */

        $this->db->addWhere($this->left, 1);
        $this->db->addWhere($this->level, 0);
        $this->db->addWhere($this->pid, 0);
        //if (!$result = $this->db->query($q)) {
        if (!$result = $this->db->select($this->id, $this->tname))
        {
            Log::write('Valami hiba van a gyökérelem lekérdezésével', true);
            return false;
        }

        switch ($this->db->affected_rows())
        {
            case 0:
                /*
                 * nincs gyökérelem, beszúrja
                 */
                $fields = array(
                    $this->left => 1,
                    $this->right => 2,
                    $this->level => 0,
                    $this->pid => 0
                );
                if (!$res2 = $this->db->insert($this->tname, $fields))
                {
                    Log::write('Valami hiba van a gyökérelem beszúrásával a switch-ben', true);
                    return false;
                }
                /*
                 * a beszúrt elem id-je lesz az objetum root_id-je
                 */
                $this->root_id = $this->db->last_insert_id();
                //$res2->free();
                $result->free();

                break;
            case 1:
                while ($row = $result->fetch_assoc())
                {
                    $this->root_id = $row[$this->id];
                }
                $result->free();
                break;

            default:
                $result->free();
                Log::write('Gyökérelem affected_rowja nem jó', true);
                return false;
                break;
        }
    }

    function updateRight($itemIDs, $muvelet, $count = 1)
    {
        if (!$itemIDs)
        {
            return;
        }
        $this->db->addWhere($this->id, '(' . implode(',', $itemIDs) . ')', 'IN');
        $datas = array(
            $this->right => $this->right . $muvelet . '2*' . $count
        );
        $this->db->update($this->tname, $datas);
    }

    function updateLeftRight($itemIDs, $muvelet, $count = 1)
    {
        if (!$itemIDs)
        {
            return;
        }
        $this->db->addWhere($this->id, '(' . implode(',', $itemIDs) . ')', 'IN');
        $datas = array(
            $this->right => $this->right . $muvelet . '2*' . $count,
            $this->left => $this->left . $muvelet . '2*' . $count
        );
        $this->db->update($this->tname, $datas);
    }

    function updateLeft($itemIDs, $muvelet, $count = 1)
    {
        if (!$itemIDs)
        {
            return;
        }
        $this->db->addWhere($this->id, '(' . implode(',', $itemIDs) . ')', 'IN');
        $datas = array(
            $this->left => $this->left . $muvelet . '2*' . $count
        );
        $this->db->update($this->tname, $datas);
    }

    function addChild($itemID, $parentID)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id || $id == 1)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. addChield<br />', true);
            return false;
        }
        $this->db->addWhere($this->id, $parentID);
        if (!$this->db->getOne($this->db->select($this->id, $this->tname)))
        {
            Log::write('Nincs ' . $parentID . ' ID-jü parent elem', true);
            return false;
        }
        $parentDatas = $this->getItemDatas($parentID);
        $parentIDs = $this->getParentIDs($parentID, true);
        $rightIDs = $this->getRightIDs($parentID);
        $this->updateLeftRight($rightIDs, '+');
        $this->updateRight($parentIDs, '+');

        $this->db->addWhere($this->id, $itemID);
        $datas = array(
            $this->left => $parentDatas[$this->right],
            $this->right => $parentDatas[$this->right] + 1,
            $this->level => $parentDatas[$this->level] + 1,
            $this->pid => $parentID
        );
        $this->db->update($this->tname, $datas);
        return $itemID;
    }

    function delItem($itemID)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id || $id == 1)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. delItem<br />', true);
            return false;
        }
        $parentIDs = $this->getParentIDs($itemID);
        if (!$parentIDs)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elemnek szülője. delItem<br />', true);
            return false;
        }
        $childIDs = $this->getChildIDs($itemID, true);
        $count = count($childIDs);
        $rightIDs = $this->getRightIDs($itemID);

        $this->updateRight($parentIDs, '-', $count);

        $this->updateLeftRight($rightIDs, '-', $count);
        return $childIDs;
    }

    function moveItem($itemID, $newParentID)
    {

        $itemDatas = $this->getItemDatas($itemID);


        $newParentDatas = $this->getItemDatas($newParentID);

        $oldParentID = $this->getParentID($itemID);

        $oldParentDatas = $this->getItemDatas($oldParentID);

        $newParentIDs = $this->getParentIDs($newParentID, true, true);

        $oldParentIDs = $this->getParentIDs($oldParentID, true, true);

        //ki kell vonni!!
        $verticalNumber = $oldParentDatas[$this->level] - $newParentDatas[$this->level];

        $movedItemIDs = $this->getChildIDs($itemID, true);

        $movedCount = count($movedItemIDs);

        $movedTurn = $newParentDatas[$this->left] - $oldParentDatas[$this->left];

        if ($movedTurn < 0)
        {
            //left
            $updateNumber = $newParentDatas[$this->right] - $itemDatas[$this->left];

            $this->db->addWhere($this->left, $newParentDatas[$this->right], '>');
            $this->db->addWhere($this->right, $itemDatas[$this->left], '<');
            $betweenIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));

            $this->updateLeft($oldParentIDs, '+', $movedCount);
            $this->updateRight($newParentIDs, '+', $movedCount);
            $this->updateLeftRight($betweenIDs, '+', $movedCount);
        } else
        {
            $updateNumber = $newParentDatas[$this->left] - $itemDatas[$this->right];

            $this->db->addWhere($this->right, $newParentDatas[$this->right], '<');
            $this->db->addWhere($this->left, $itemDatas[$this->right], '>');
            $betweenIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));

            $this->updateLeft($newParentIDs, '-', $movedCount);
            $this->updateRight($oldParentIDs, '-', $movedCount);
            $this->updateLeftRight($betweenIDs, '-', $movedCount);
        }
        $datas = array(
            $this->left => $this->left . '+' . $updateNumber,
            $this->right => $this->right . '+' . $updateNumber,
            $this->level => $this->level . '-' . $verticalNumber,
        );
        $this->db->addWhere($this->id, '(' . implode(',', $movedItemIDs) . ')', 'IN');
        $this->db->update($this->tname, $datas);
        $this->db->addWhere($this->id, $itemID);
        $this->db->update($this->tname, array($this->pid), array($newParentID));
    }

    function getChildIDsInLevel($itemID, $level, $all = false)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. getChildIDsInLevel <br />', true);
            return false;
        }
        $itemDatas = $this->getItemDatas($itemID);
        if ($level <= $itemDatas[$this->level])
        {
            Log::write('Az ' . $itemID . ' ID-jü elem levelje ' . $itemDatas[$this->level] .
                    '. <br /> A kért level: ' . $level . '. <br /> Felfele nincs gyerek!!!', true);
            return false;
        }
        $childIDs = $this->getChildIDs($itemID);
        if (!$childIDs)
        {
            return false;
        }
        $this->db->addWhere($this->id, '(' . implode(',', $childIDs) . ')', 'IN');
        if ($all == true)
        {
            $this->db->addWhere($this->level, $level, '<=');
            $this->db->addWhere($this->level, $itemDatas[$this->level], '>');
        } else
        {
            $this->db->addWhere($this->level, $level);
        }
        $selectedChieldIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));
        if (!$selectedChieldIDs)
        {
            return false;
        }
        return $selectedChieldIDs;
    }

    function getParentIDsInLevel($itemID, $level, $all = false)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id || $id == 1)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. getParentIDsInLevel <br />', true);
            return false;
        }
        $itemDatas = $this->getItemDatas($itemID);
        if ($level >= $itemDatas[$this->level])
        {
            Log::write('Az ' . $itemID . ' ID-jü elem levelje ' . $itemDatas[$this->level] .
                    '. <br /> A kért level: ' . $level . '. <br /> Lefele nincs szülő!!!', true);
            return false;
        }
        $parentIDs = $this->getParentIDs($itemID);
        if (!$parentIDs)
        {
            return false;
        }
        $this->db->addWhere($this->id, '(' . implode(',', $parentIDs) . ')', 'IN');
        if ($all == true)
        {
            //(1,2,3,4,5)
            $this->db->addWhere($this->level, $level, '>=');
            $this->db->addWhere($this->level, $itemDatas[$this->level], '<');
        } else
        {
            $this->db->addWhere($this->level, $level);
        }
        $selectedParentIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));
        if (!$selectedParentIDs)
        {
            return false;
        }
        return $selectedParentIDs;
    }

    function getRightIDs($itemID)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. getRightIDs<br />', true);
            return false;
        }
        $itemDatas = $this->getItemDatas($itemID);
        $this->db->addWhere($this->left, $itemDatas[$this->left], '>');
        $this->db->addWhere($this->right, $itemDatas[$this->right], '>');
        $rightIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));
        if (!$rightIDs)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elemnek jobb oldali követője.<br /> getRightIDs', true);
            return false;
        }
        return $rightIDs;
    }

    function getParentID($itemID)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. getParentID<br />', true);
            return false;
        }
        $itemDatas = $this->getItemDatas($itemID);
        $this->db->addWhere($this->id, $itemDatas[$this->pid]);
        $parentID = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$parentID)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elemnek szülője', true);
            return false;
        }
        return $parentID;
    }

    function getChildIDs($itemID, $mitItem = false)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. getChildIDs<br />', true);
            return false;
        }
        if ($mitItem)
        {
            $muvelet = '=';
        } else
        {
            $muvelet = '';
        }
        $itemDatas = $this->getItemDatas($itemID);
        $this->db->addWhere($this->left, $itemDatas[$this->left], '>' . $muvelet);
        $this->db->addWhere($this->right, $itemDatas[$this->right], '<' . $muvelet);
        $childIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));
        if (!$childIDs)
        {
            Log::write('Nincs ' . $itemID . 'ID-jü elemnek szülője', true);
            return false;
        }
        return $childIDs;
    }

    function getParentIDs($itemID, $mitItem = false, $withoutRoot = false)
    {
        $this->db->addWhere($this->id, $itemID);
        $id = $this->db->getOne($this->db->select($this->id, $this->tname));
        if (!$id)
        {
            Log::write('Nincs ' . $itemID . ' ID-jü elem vagy root. getParentIDs<br />', true);
            return false;
        }
        if ($mitItem)
        {
            $muvelet = '=';
        } else
        {
            $muvelet = '';
        }
        $itemDatas = $this->getItemDatas($itemID);
        if ($withoutRoot)
        {
            $this->db->addWhere($this->id, 1, '<>');
        }
        $this->db->addWhere($this->left, $itemDatas[$this->left], '<' . $muvelet);
        $this->db->addWhere($this->right, $itemDatas[$this->right], '>' . $muvelet);
        $parentIDs = $this->db->getArrayOne($this->db->select($this->id, $this->tname));
        if (!$parentIDs)
        {
            Log::write('Nincs ' . $itemID . 'ID-jü elemnek szülője', true);
            return false;
        }
        return $parentIDs;
    }

    function getItemDatas($itemID)
    {
        $this->db->addWhere($this->id, $itemID);
        $fields = array($this->left, $this->right, $this->pos, $this->level, $this->pid);
        $itemDatas = $this->db->getRow($this->db->select($fields, $this->tname));
        if (!$itemDatas)
        {
            Log::write('Nincs ' . $itemID . 'ID-jü elem', true);
            return false;
        }
        return $itemDatas;
    }

    function getTree()
    {
        $array = $this->db->getArray(sprintf('SELECT * FROM %s ORDER BY %s', $this->tname, $this->pref . 'left'));
        return $array;
    }

}
