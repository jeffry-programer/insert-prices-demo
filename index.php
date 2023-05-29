<?php

include "../insert-prices/controller.php";
include "../insert-prices/model.php";

$controller = new Controller();
$bussiness = $controller->ctrlQueryAllRegister('empresa', 'nombreEmpresa');
$categories = $controller->ctrlQueryCategories();

set_time_limit(5000);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Scraping</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>
<body>
    <div class="row text-center">
        <h2 class="mt-3">Insertar precios</h2>
        <div class="col">
        </div>
        <div class="col align-self-center justify-content-center">
            <div class="card w-100 mt-3">
                <div class="card-body">
                    <form method="post">
                        <label for="exampleInputEmail1" class="form-label">Empresa</label>
                        <select name="bussiness" class="form-select">
                            <?php foreach($bussiness as $key): ?>
                            <option value="<?php echo $key['idEmpresa']; ?>"><?php echo $key['nombreEmpresa']; ?></option>
                            <?php endforeach ?>
                        </select>
                        <label for="exampleInputEmail1" class="form-label mt-2">Categoria</label>
                        <select name="category" class="form-select">
                            <?php foreach($categories as $key): ?>
                            <option value="<?php echo $key['idCategoria']; ?>"><?php echo $key['nombreCategoria']; ?></option>
                            <?php endforeach ?>
                        </select>
                        <?php 
                            if(isset($_POST['bussiness'])){
                                $controller->processData();
                            }
                        ?>
                        <button type="submit" class="btn btn-primary w-100 mt-3" id="scraper-btn">Insertar</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col">
        </div>
    </div>
</body>
</html>