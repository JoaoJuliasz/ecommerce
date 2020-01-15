<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use Hcode\Mailer;
use \Hcode\Model;

class User extends Model
{

    //secret precisa ter 16 carac.
    const SECRET = "HcodePhp7_Secret";
    const SECRET_IV = "HcodePhp7_Secret_IV";
    const ERROR = "UserError";
    const ERROR_REGISTER = "UserErrorRegister";
    const SUCCESS = 'UserSuccess';

    const SESSION = "User";

    public static function getFromSession()
    {
        $user = new User;

        if (isset($_SESSION[User::SESSION]) && (int) $_SESSION[User::SESSION]['iduser'] > 0) {
            $user->setData($_SESSION[User::SESSION]);
        }
        return $user;
    }

    public static function checkLogin($inadmin = true)
    {
        if (
            !isset($_SESSION[User::SESSION]) || !$_SESSION[User::SESSION]
            || !(int) $_SESSION[User::SESSION]['iduser'] > 0
        ) {
            return false;
        } //nao esta logado
        else {
            if ($inadmin === true && (bool) $_SESSION[User::SESSION]['inadimin'] === true) {
                return true;
            } else if ($inadmin === false) {
                return true;
            } else {
                return false;
            }
        }
    }
    public static function login($login, $password)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a inner join tb_persons b on a.idperson = b.idperson where a.deslogin = :LOGIN", array(
            ":LOGIN" => $login
        ));
        if (count($results) === 0) {
            throw new \Exception("Usuario e senha noa existe");
        }
        $data = $results[0];

        if (password_verify($password, $data["despassword"]) === true) {
            $user = new User();
            $user->setData($data);

            $_SESSION[User::SESSION] = $user->getValues();

            return $user;
        } else {
            throw new \Exception("Usuario e senha noa existe");
        }
    }

    public static function verifyLogin($inadmin = true)
    {
        if (User::checkLogin($inadmin)) {
            header("Location: /admin/login");
        } else {
            header("Location: /login");
        }
    }

    public static function logout()
    {

        $_SESSION[User::SESSION] = NULL;
    }

    public static function listAll()
    {
        $sql = new Sql();
        return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {
        $sql = new Sql;
        $results = $sql->select("CALL sp_users_save (:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

            ":desperson" => $this->getdesperson(),

            ":deslogin" => $this->getdeslogin(),

            ":despassword" => User::getPasswordHash($this->getdespassword()),

            ":desemail" => $this->getdesemail(),

            ":nrphone" => $this->getnrphone(),

            ":inadmin" => $this->getinadmin()

        ));

        $this->setData($results[0]);
    }

    public function get($iduser)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) where a.iduser = :iduser", array(
            ":iduser" => $iduser
        ));

        $this->setData($results[0]);
    }

    public function update()
    {
        $sql = new Sql;
        $results = $sql->select("CALL sp_usersupdate_save (:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(

            ":iduser" => $this->getiduser(),

            ":desperson" => $this->getdesperson(),

            ":deslogin" => $this->getdeslogin(),

            ":despassword" => User::getPasswordHash($this->getdespassword()),

            ":desemail" => $this->getdesemail(),

            ":nrphone" => $this->getnrphone(),

            ":inadmin" => $this->getinadmin()

        ));

        $this->setData($results[0]);
    }

    public function delete()
    {
        $sql = new Sql;
        $resulst = $sql->select("CALL sp_users_delete(:iduser)", array(
            ":iduser" => $this->getiduser()
        ));
    }

    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql();
        $results = $sql->select("SELECT * from tb_persons a inner join tb_users b using (idperson) where a.desemail = :email", array(
            ":email" => $email
        ));

        if (count($results) === 0) {
            throw new \Exception("Nao foi possivel recuperar a senha");
        } else {

            $data = $results[0];

            $results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
                ":iduser" => $data["iduser"],
                ":desip" => $_SERVER["REMOTE_ADDR"]
            ));

            if (count($results2) === 0) {
                throw new \Exception("Nao foi possivel recuperar a senha");
            } else {
                $dataRecovery = $results2[0];
                //o idrecovery sera criptografado
                $code = openssl_encrypt(
                    $dataRecovery['idrecovery'],
                    'AES-128-CBC',
                    pack("a16", User::SECRET),
                    0,
                    pack("a16", User::SECRET_IV)
                );

                $code =  base64_encode($code);
                if($inadmin === true){
                    $link = "http://www.joaocommerce.com.br/admin/forgot/reset?code=$code";

                }else{
                    $link = "http://www.joaocommerce.com.br/forgot/reset?code=$code";

                }

                $mailer = new Mailer($data["desemail"], $data["desperson"], "Redefinir senha da joao store", "forgot", array(
                    "name" => $data["desperson"],
                    "link" => $link
                ));

                $mailer->send();

                return $data;
            }
        }
    }

    public static function validForgotDecrypt($code)
    {
        $code = base64_decode($code);
        $idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
        $sql = new Sql();
        $result = $sql->select("SELECT * from tb_userspasswordsrecoveries a inner join tb_users b using(iduser) inner join tb_persons c using (idperson) 
        where a.idrecovery = :recovery and a.dtrecovery is null and date_add(a.dtregister, interval 1 hour) >= now()", array(
            ":recovery" => $idrecovery
        ));

        if (count($result) === 0) {
            throw new \Exception("Nao foi possivel recuperar a senha");
        } else {

            return $result[0];
        }
    }
    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql();
        $sql->query("UPDATE tb_userspasswordsrecoveries set dtrecovery = now() where idrecovery = :idrecovery", array(
            ":idrecovery" => $idrecovery
        ));
    }

    public function setPassword($password)
    {
        $sql = new Sql;
        $sql->query("UPDATE tb_users set despassword = :password where iduser = :iduser", array(
            ":password" => $password,
            ":iduser" => $this->getiduser()
        ));
    }

    public static function setSuccess($msg)
    {
        $_SESSION[User::SUCCESS] = $msg;
    }

    public static function getsuccess()
    {

        $msg = (isset($_SESSION[User::SUCCESS]) && $_SESSION[User::SUCCESS]) ? $_SESSION[User::SUCCESS] : '';
        User::clearsuccess();

        return $msg;
    }

    public static function clearsuccess()
    {
        $_SESSION[User::ERROR] = NULL;
    }
    public static function setError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError()
    {

        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? $_SESSION[User::ERROR] : '';
        User::clearError();

        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }
    public static function getErrorRegister()
    {

        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? $_SESSION[User::ERROR_REGISTER] : '';
        User::clearErrorRegister();

        return $msg;
    }

    public static function clearErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }


    public static function checkLoginExists($login)
    {
        $sql = new Sql;

        $results = $sql->select("SELECT * from tb_users where deslogin = :deslogin", [
            ':deslogin' => $login
        ]);
    }

    public function getPasswordHash($password){
        return password_hash($password, PASSWORD_DEFAULT,[
            'cost'=>12
        ]);
    }
}
