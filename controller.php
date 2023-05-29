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
            if ($linkBussiness !== false) {
                if ($linkBussiness['paginaWeb'] !== NULL) {
                    $webPageBussiness = $linkBussiness['paginaWeb'];
                    $nameBussiness = $linkBussiness['nombreEmpresa'];
                    $controller = new Controller();
                    foreach ($response as $index => $key){
                        $prodId = $model->queryProdId($key['Nombre']);
                        $controller->selectBussiness($key['Nombre'], $webPageBussiness, $prodId['idProducto'], $bussinessId, $nameBussiness);
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
                }
            }
        }
        return $response;
    }

    public function selectBussiness($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness)
    {
        $controller  = new Controller();
        if ($nameBussiness == "Carrulla") {
            $controller->scrapeDataCarulla($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else if ($nameBussiness == "Los Montes") {
            $controller->scrapeDataLosMontes($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
        } else {
            $controller->scrapeDataBetel($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness);
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
                } else {
                    $arrayProds = explode("productName", $text);
                    foreach ($arrayProds as $index => $key) {
                        if ($index !== 0) {
                            $nameProduct = explode("&quot;", $key)[2];
                            similar_text($nameProd, $nameProduct, $percent);
                            $percent = round($percent, 2);
                            if ($percent >= 90) {
                                $price = str_replace(":", "", explode(",", explode("&quot;Price&quot;", $key)[1])[0]);
                                $arrayCleanLinkProd = [":", "&quot;"];
                                $linkProd = $webPageBussiness . str_replace($arrayCleanLinkProd, "", explode(",", explode("&quot;linkText&quot;", $key)[1])[0]) . '/p';
                                $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                                $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                                break;
                            }
                            if ($index == count($arrayProds) - 1) {
                                $string = $nameProd . '<br>';
                                $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
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
        foreach ($content as $key) {
            $text = htmlspecialchars($key);
            $contentFinal = $contentFinal . $text;
        }
        $array = explode("h3 product-title", $contentFinal);
        $controller = new Controller();
        if (count($array) == 1) {
            $string = $nameProd . '<br>';
            $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
        } else {
            $arrayReplace = ["a", "&lt;", "/"];
            foreach ($array as $index => $key) {
                $nameProduct = str_replace($arrayReplace, "", explode("&gt;", $key)[2]);
                similar_text($nameProd, $nameProduct, $percent);
                $percent = round($percent, 2);
                if ($percent >= 90){
                    $price = str_replace("&gt;", "", explode("&lt;", explode("&quot;price&quot;", $key)[1])[0]);
                    $price = intval(preg_replace('/[^0-9]+/', '', $price), 10); 
                    $linkProd = explode("&quot;" , $key)[2];
                    $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                    $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                    break;
                }
                if ($index == count($array) - 1) {
                    $string = $nameProd . '<br>';
                    $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                }
            }
        }
    }

    public function scrapeDataBetel($nameProd, $webPageBussiness, $prodId, $bussinessId, $nameBussiness)
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
                } else {
                    $arrayProds = explode("productName", $text);
                    foreach ($arrayProds as $index => $key) {
                        if ($index !== 0) {
                            $nameProduct = explode("&quot;", $key)[2];
                            similar_text($nameProd, $nameProduct, $percent);
                            $percent = round($percent, 2);
                            if ($percent >= 90) {
                                $price = str_replace(":", "", explode(",", explode("&quot;Price&quot;", $key)[1])[0]);
                                $arrayCleanLinkProd = [":", "&quot;"];
                                $linkProd = $webPageBussiness . str_replace($arrayCleanLinkProd, "", explode(",", explode("&quot;linkText&quot;", $key)[1])[0]) . '/p';
                                $string = 'INSERT INTO producto_has_empresa (Producto_idProducto, Empresa_idEmpresa, precioReal, linkProducto) VALUES (' . $prodId . ',' . $bussinessId . ',' . $price . ',"' . $linkProd . '");';
                                $controller->addTextFile2('files/' . $nameBussiness . '/productInserts.sql', $string);
                                break;
                            }
                            if ($index == count($arrayProds) - 1) {
                                $string = $nameProd . '<br>';
                                $controller->addTextFile('files/' . $nameBussiness . '/prodsNotFound.txt', $string);
                            }
                        }
                    }
                }
            }
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
}
