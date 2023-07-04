<?php

class Controller
{
    public function ctrlQueryAllRegister($table, $attr)
    {
        $model = new GeneralModel();
        $res = $model->mdlQueryAllRegister($table, $attr);
        return $res;
    }

    public function ctrlQueryCategories()
    {
        $model = new GeneralModel();
        $res = $model->mdlQueryCategories();
        return $res;
    }

    public function processData()
    {
        $start = microtime(true);
        $bussinessId = $_POST['bussiness'];
        $categoryId =  $_POST['category'];
        $model = new GeneralModel();
        $response =  $model->mdlQueryProdsToSearch($bussinessId, $categoryId);
        if (count($response) > 0) {
            $linkBussiness = $model->queryBussinesLink($bussinessId);
            if ($linkBussiness['paginaWeb'] !== NULL){
                $webPageBussiness = $linkBussiness['paginaWeb'];
                $nameBussiness = $linkBussiness['nombreEmpresa'];
                $controller = new Controller();
                foreach ($response as $index => $key){
                    $position = $controller->readPosition($nameBussiness);
                    if($index == $position){
                        $prodId = $model->queryProdId($key['Nombre']);
                        $res = $controller->selectBussiness($key['Nombre'], $webPageBussiness, $prodId['idProducto'], $bussinessId, $nameBussiness);
                        if($res == "ok"){
                            $newPosition = $position + 1;
                            $controller->savePosition($nameBussiness, $newPosition);
                        }else{
                            break;
                        }
                    }
                }
                $time_elapsed = microtime(true) - $start;

                echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

                echo "<script>
                        Swal.fire({
                        icon: 'success',
                        title: 'Archivo scrapeado exitosamente',
                        text: 'Tiempo de ejecución: " . $time_elapsed . "s',
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                            if (result.isConfirmed){
                                window.location.replace('https://localhost/insert-prices');
                            }
                        });
                    </script>";
            }else{
                echo "<script>
                        Swal.fire({
                        icon: 'info',
                        title: 'La empresa no tiene un link registrado',
                        confirmButtonText: 'Aceptar'
                    }).then((result) => {
                            if (result.isConfirmed){
                                window.location.replace('https://localhost/insert-prices');
                            }
                        });
                    </script>";
            }
        }
        return $response;
    }

    public function selectBussiness($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness)
    {
        $controller  = new Controller();
        if ($nameBussiness == "Carrulla") {
            $res = $controller->scrapeDataCarulla($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else if ($nameBussiness == "Los Montes") {
            $res = $controller->scrapeDataLosMontes($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else if ($nameBussiness == "Comunal") {
            $res = $controller->scrapeDataComunal($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else if ($nameBussiness == "Exito") {
            $res = $controller->scrapeDataExito($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else if ($nameBussiness == "Jumbo") {
            $res = $controller->scrapeDataJumbo($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else if ($nameBussiness == "Metro") {
            $res = $controller->scrapeDataMetro($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        }
        return $res;
    }

    public function scrapeDataMetro($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness){
        $searchProd = str_replace(" ", "%20", $webPageBussiness.$nameProd."?_q=".$nameProd."&map=ft");
        $content = file($searchProd);
        $controller = new Controller();
        foreach($content as $index => $key){
            $text = htmlspecialchars($key);
            if(str_contains($text, 'productName')){
                $arrayProducts = explode("productName", $text);
                foreach($arrayProducts as $index => $key2){
                    if($index != 0){
                        $nameProduct = explode("&quot;", $key2)[2];
                        $response = $controller->compareNameProd($nameProd, $nameProduct);
                        if ($response == "ok"){
                            $linkProd = explode("&quot;", explode("linkText", $key2)[1])[2];
                            $price = intval(preg_replace('/[^0-9]+/', '', explode("&quot;", explode("lowPrice", $key2)[1])[1]));
                            $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                            $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                            break;
                        }
                        if ($index == count($arrayProducts) - 1) {
                            $string = $nameProd . '<br>';
                            $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                        }
                    }
                }
                break;
            }
            if($index == (count($content) - 1)){
                $string = $nameProd . '<br>';
                $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
            }
        }
    }

    public function scrapeDataExito($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness){ 
        $searchProd = str_replace(" ", "%20", $webPageBussiness.$nameProd."?_q=".$nameProd."&map=ft");
        $content = file($searchProd);
        foreach($content as $index => $key){
            if($index == 333){
                $arrayProducts = explode("productName", htmlspecialchars($key));
                $controller = new Controller();
                if(count($arrayProducts) == 1){
                    $string = $nameProd . '<br>';
                    $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                    break;
                }
                foreach($arrayProducts as $index => $key2){
                    if(isset(explode("linkText", $key2)[1])){
                        $nameProduct = str_replace(":{", "", explode("&quot;", $key2)[2]);
                        $response = $controller->compareNameProd($nameProd, $nameProduct);
                        if ($response == "ok"){
                            $linkProd = $webPageBussiness.explode("&quot;", explode("linkText", $key2)[1])[2].'/p';
                            $price = intval(preg_replace('/[^0-9]+/', '', explode("&quot;", explode("lowPrice", $key2)[1])[1]), 10);
                            $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                            $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                            break;
                        }
                        if ($index == count($arrayProducts) - 1) {
                            $string = $nameProd . '<br>';
                            $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                        }
                    }
                }
            }
        }
    }

    public function scrapeDataJumbo($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness){
        $searchProd = str_replace(" ", "%20", $webPageBussiness.$nameProd."?_q=".$nameProd."&map=ft");
        $content = file($searchProd);
        foreach($content as $index => $key){
            $text = htmlspecialchars($key);
            if(str_contains($text, "Product")){
                $arrayProducts = explode("productName", htmlspecialchars($key));
                $controller = new Controller();
                if(count($arrayProducts) == 1){
                    $string = $nameProd . '<br>';
                    $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                    break;
                }
                foreach($arrayProducts as $index => $key2){
                    if(isset(explode("linkText", $key2)[1])){
                        $nameProduct = str_replace(":{", "", explode("&quot;", $key2)[2]);
                        similar_text($nameProd, $nameProduct, $percent);
                        $response = $controller->compareNameProd($nameProd, $nameProduct);
                        if ($response == "ok"){
                            $linkProd = $webPageBussiness.explode("&quot;", explode("linkText", $key2)[1])[2].'/p';
                            $price = intval(preg_replace('/[^0-9]+/', '', explode("&quot;", explode("lowPrice", $key2)[1])[1]), 10);
                            $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                            $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                            break;
                        }
                        if ($index == count($arrayProducts) - 1) {
                            $string = $nameProd . '<br>';
                            $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                        }
                    }
                }
            }
        }
    }

    public function scrapeDataCarulla($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness)
    {
        $searchProd = str_replace(" ", "%20", $nameProd) . '?_q=' . str_replace(" ", "%20", $nameProd) . '&map=ft';
        $content = file($webPageBussiness . $searchProd);
        foreach ($content as $index => $key) {
            if ($index == 262) {
                $text = htmlspecialchars($key);
                $array = explode("}", $text);
                $controller = new Controller();
                $string = "";
                if (str_contains($array[0], '$ROOT_QUERY.productSearch')) {
                    $string = $nameProd . '<br>';
                    $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                    return "ok";
                } else {
                    $arrayProds = explode("productName", $text);
                    foreach ($arrayProds as $index => $key) {
                        if ($index !== 0) {
                            $nameProduct = explode("&quot;", $key)[2];
                            $response = $controller->compareNameProd($nameProd, $nameProduct);
                            if ($response == "ok"){
                                $price = str_replace(":", "", explode(",", explode("&quot;Price&quot;", $key)[1])[0]);
                                $arrayCleanLinkProd = [":", "&quot;"];
                                $linkProd = $webPageBussiness . str_replace($arrayCleanLinkProd, "", explode(",", explode("&quot;linkText&quot;", $key)[1])[0]) . '/p';
                                $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                                $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                                return "ok";
                            }
                            if ($index == count($arrayProds) - 1) {
                                $string = $nameProd . '<br>';
                                $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                                return "ok";
                            }
                        }
                    }
                }
            }
        }
    }

    public function scrapeDataLosMontes($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness)
    {
        $searchProd = "site/busqueda?controller=search&s=" . str_replace(" ", "+", $nameProd);
        $content = file($webPageBussiness . $searchProd);
        $contentFinal = "";
        foreach ($content as $key){
            $text = htmlspecialchars($key);
            $contentFinal = $contentFinal . $text;
        }
        $array = explode("h3 product-title", $contentFinal);
        $controller = new Controller();
        if (count($array) == 1) {
            $string = $nameProd . '<br>';
            $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
            return "ok";
        } else {
            $arrayReplace = ["a", "&lt;", "/"];
            foreach ($array as $index => $key) {
                $nameProduct = str_replace($arrayReplace, "", explode("&gt;", $key)[2]);
                $response = $controller->compareNameProd($nameProd, $nameProduct);
                if ($response == "ok"){
                    $price = str_replace("&gt;", "", explode("&lt;", explode("&quot;price&quot;", $key)[1])[0]);
                    $price = intval(preg_replace('/[^0-9]+/', '', $price), 10);
                    $linkProd = explode("&quot;", $key)[2];
                    $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                    $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                    return "ok";
                }
                if ($index == count($array) - 1) {
                    $string = $nameProd . '<br>';
                    $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                    return "ok";
                }
            }
        }
    }

    public function scrapeDataComunal($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness)
    {
        $searchProd = "jolisearch?s=" . str_replace(" ", "+", $nameProd);
        $content = file($webPageBussiness . $searchProd);
        $contentFinal = "";

        foreach ($content as $key) {
            $text = htmlspecialchars($key);
            $contentFinal = $contentFinal . $text . '<br>';
        }


        $array = explode("product_item", $contentFinal);

        $controller = new Controller();

        if(count($array) > 1){
            foreach($array as $index => $key){
                $nameProduct = explode("&lt;", explode("&quot;&gt;", $key)[4])[0];
                if($index !== 0){
                    $response = $controller->compareNameProd($nameProd, $nameProduct);
                    if ($response == "ok"){
                        $price =  intval(preg_replace('/[^0-9]+/', '', explode("&lt;", explode("&quot;price&quot;", $key)[1])[0]), 10);
                        $linkProd = explode('&quot;', explode('&lt;h4&gt;&lt;a href=&quot;', $key)[1])[0];
                        $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                        $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                        return "ok";
                    }
                    if ($index == count($array) - 1) {
                        $string = $nameProd . '<br>';
                        $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                        return "ok";
                    }
                }
            }
        }else{
            $string = $nameProd . '<br>';
            $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
            return "ok";
        }
    }

    public function addTextFile($route, $text)
    {
        $txt = fopen($route, "r");
        $txtFinal = "";
        while (($data = fgetcsv($txt, 1000, ",")) !== FALSE) {
            $numero = count($data);
            $rowSend = "";
            for ($c = 0; $c < $numero; $c++) {
                $rowSend = $rowSend . $data[$c];
            }
            $txtFinal = $txtFinal . $rowSend;
        }
        $txtFinal = $txtFinal . $text;
        $txtEdit = fopen($route, "w") or die("Error al crear el archivo");

        fwrite($txtEdit, $txtFinal);
    }

    public function addTextFile2($route, $text)
    {
        $txt = fopen($route, "r");
        $txtFinal = "";
        while (($data = fgetcsv($txt, 1000, ";")) !== FALSE) {
            $numero = count($data);
            $rowSend = "";
            for ($c = 0; $c < $numero; $c++) {
                $rowSend = $rowSend . $data[$c] . ';';
            }
            $txtFinal = $txtFinal . $rowSend;
        }
        $txtFinal = $txtFinal . $text;
        $txtFinal = str_replace(";;", ";", $txtFinal);
        $txtEdit = fopen($route, "w") or die("Error al crear el archivo");

        fwrite($txtEdit, $txtFinal);
    }
    
    public function compareNameProd($nameProd, $nameProduct){
        if(str_contains(strtolower($nameProduct), "kg")){
            $nameProduct = str_replace("kg","000g", strtolower($nameProduct));
        }

        if(str_contains(strtolower($nameProd), " gr")){
            $nameProd = str_replace(" gr","g", strtolower($nameProd));
        }

        if(str_contains(strtolower($nameProd), " x ")){
            $nameProd = str_replace("x"," ", strtolower($nameProd));
        }

        if(str_contains(strtolower($nameProd), "kg")){
            $nameProd = str_replace("kg","000g", strtolower($nameProd));
        }

        if(str_contains(strtolower($nameProduct), " gr")){
            $nameProduct = str_replace(" gr","g", strtolower($nameProduct));
        }

        if(str_contains(strtolower($nameProduct), " x ")){
            $nameProduct = str_replace("x"," ", strtolower($nameProduct));
        }
        

        if(intval(preg_replace('/[^0-9]+/', '', $nameProd), 10) !== 0 && intval(preg_replace('/[^0-9]+/', '', $nameProduct), 10) !== 0 && intval(preg_replace('/[^0-9]+/', '', $nameProd), 10) !== intval(preg_replace('/[^0-9]+/', '', $nameProduct), 10)){
            return "error";
        }

        $array = explode(" ", strtolower($nameProd));
        $total = 0;
        foreach($array as $key){
            similar_text(strtolower($key), strtolower($nameProduct), $percent);
            $total = $percent + $total;
        }
        $total = $total;

        if($total >= 100){
            return "ok";
        }else{
            return "error";
        }
    }

    public function savePosition($nameBussiness, $position){
        $txtEdit = fopen("files/".$nameBussiness."/ultimatePosition.txt", "w") or die("Error al crear el archivo");
        fwrite($txtEdit, $position);
    } 

    public function readPosition($nameBussiness){
        $txt = fopen("files/".$nameBussiness."/ultimatePosition.txt", "r");
        while (($data = fgetcsv($txt, 1000, ",")) !== FALSE) {
            $numero = count($data);
            $rowSend = "";
            for ($c = 0; $c < $numero; $c++){
                $rowSend = $rowSend . $data[$c];
            }
        }
        
        return $rowSend;
    }
}
