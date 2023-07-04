<?php
    require_once "connection.php";

    class GeneralModel{
        public function mdlQueryAllRegister($table, $attr){
            $stmt = Conexion::conectar()->prepare("SELECT * FROM $table ORDER BY $attr ASC");
            $stmt->execute();
            $res = $stmt->fetchAll();
            $stmt = null;
            return $res;
        }

        public function mdlQueryCategories(){
            $stmt = Conexion::conectar()->prepare("SELECT * FROM categoria WHERE controlCategoria != 1 ORDER BY nombreCategoria ASC");
            $stmt->execute();
            $res = $stmt->fetchAll();
            $stmt = null;
            return $res;
        }

        public function mdlQueryProdsToSearch($bussinessId, $categoryId){
            $stmt = Conexion::conectar()->prepare("SELECT DISTINCT Nombre FROM producto t1 INNER JOIN subcategoria t3 ON t1.subCategoria_idsubCategoria = t3.idsubCategoria WHERE t3.Categoria_idCategoria = :categoryId AND NOT EXISTS (SELECT NULL FROM producto_has_empresa t2 WHERE t2.Producto_idProducto = t1.idProducto AND t2.Empresa_idEmpresa = :bussinessId) ORDER BY Nombre ASC");
            $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
            $stmt->bindParam(':bussinessId', $bussinessId, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetchAll();
            $stmt = null;
            return $res;
        }

        public function queryBussinesLink($bussinessId){
            $stmt = Conexion::conectar()->prepare("SELECT paginaWeb, nombreEmpresa FROM empresa WHERE idEmpresa = :bussinessId");
            $stmt->bindParam(':bussinessId', $bussinessId, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetch();
            $stmt = null;
            return $res;
        }

        public function queryProdId($nameProd){
            $stmt = Conexion::conectar()->prepare("SELECT idProducto FROM producto WHERE Nombre = :nameProd");
            $stmt->bindParam(':nameProd', $nameProd, PDO::PARAM_STR);
            $stmt->execute();
            $res = $stmt->fetch();
            $stmt = null;
            return $res;
        }
    }

?>