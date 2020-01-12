<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Mailer;
use \Hcode\Model;

class Category extends Model
{
    public static function listAll()
    {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_categories order by descategory");
    }


    public function save()
    {
        $sql = new Sql;
        $results = $sql->select("CALL sp_categories_save (:idcategory, :descategory)", array(

            ":idcategory" => $this->getidcategory(),

            ":descategory" => $this->getdescategory()

        ));

        $this->setData($results[0]);
    }

    public function get($idcategory)
    {
        $sql = new Sql;
        $results = $sql->select("SELECT * from tb_categories where idcategory = :idcategory", array(
            ":idcategory" => $idcategory
        ));

        $this->setData($results[0]);
    }

    public function delete(){
        $sql = new Sql;
        $sql->query("DELETE from tb_categories where idcategory = :idcategory", array(
            ":idcategory" => $this->getidcategory()
        ));
    }
}
