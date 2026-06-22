<?php
if (!function_exists('letra_mes2')) {
    function letra_mes2($mes) {
        $meses = ["01"=>"Ene", "02"=>"Feb", "03"=>"Mar", "04"=>"Abr", "05"=>"May", "06"=>"Jun", "07"=>"Jul", "08"=>"Ago", "09"=>"Sep", "10"=>"Oct", "11"=>"Nov", "12"=>"Dic"];
        return $meses[$mes] ?? $mes;
    }
}
if (!function_exists('convertir')) {
    function convertir($number) {
        return number_format($number, 2) . " PESOS 00/100 M.N.";
    }
}
?>
<style type="text/css">
    <!--
    div.minifiche {
        position: relative;
        overflow: hidden;
        width: 454px;
        height: 138px;
        padding: 0;
        font-size: 11px;
        text-align: left;
        font-weight: normal;
    }

    div.minifiche img.icone {
        position: absolute;
        border: none;
        left: 5px;
        top: 5px;
        width: 240px;
        height: 128px;
        overflow: hidden;
    }

    div.minifiche div.zone1 {
        position: absolute;
        border: none;
        left: 257px;
        top: 8px;
        width: 188px;
        height: 14px;
        padding-top: 1px;
        overflow: hidden;
        text-align: center;
        font-weight: bold;
    }

    div.minifiche div.zone2 {
        position: absolute;
        border: none;
        left: 315px;
        top: 28px;
        width: 131px;
        height: 14px;
        padding-top: 1px;
        overflow: hidden;
        text-align: left;
        font-weight: normal;
    }

    div.minifiche div.zone3 {
        position: absolute;
        border: none;
        left: 315px;
        top: 48px;
        width: 131px;
        height: 14px;
        padding-top: 1px;
        overflow: hidden;
        text-align: left;
        font-weight: normal;
    }

    div.minifiche div.zone4 {
        position: absolute;
        border: none;
        left: 315px;
        top: 68px;
        width: 131px;
        height: 14px;
        padding-top: 1px;
        overflow: hidden;
        text-align: left;
        font-weight: normal;
    }

    div.minifiche div.zone5 {
        position: absolute;
        border: none;
        left: 315px;
        top: 88px;
        width: 131px;
        height: 14px;
        padding-top: 1px;
        overflow: hidden;
        text-align: left;
        font-weight: normal;
    }

    div.minifiche div.download {
        position: absolute;
        border: none;
        left: 257px;
        top: 108px;
        width: 188px;
        height: 22px;
        overflow: hidden;
        text-align: center;
        font-weight: normal;
    }
    -->
</style>

<page>


    <table style="width: 100%" border="0 ">
        <tr>
            <td style="border: solid 0px #007700; width: 20%;  ">
                <img src="<?=img.'logo.jpg' ?>" height="90" width="170">
            </td>
            <td style="  width: 50%; ">
                <p style="margin-top: -31">Calle:
                    <?=$fila_empresa['calle']?> , Colonia:
                    <?=$fila_empresa['colonia']?>, Codigo Postal:
                    <?=$fila_empresa['codigo_postal']?>, Delegacion:
                    <?=$fila_empresa['delegacion']?>
                </p>
                <div style="width: 78%">
                    <hr>
                </div>
                <?=$fila_empresa['giro']?>
            </td>
            <td style=" width: 30%">
                <p style="margin-top: ">
                    <img src="<?=img.'whats.jpg' ?>" height="20" width="30">

                    Tel:
                    <?=$fila_empresa['telefono']?>
                </p>
                <p style="margin-top: -8">
                    Email:
                    <?=$fila_empresa['email']?>
                </p>
                <p style="margin-top: -8">
                    Web:
                    <?=$fila_empresa['web']?>
                </p>
                <h3>
                    Folio:
                    <?=$data['folio']?>

                </h3>

            </td>
        </tr>
    </table>


    <table style="width: 100%" border="0">
        <tr>
            <td style=" width: 60%" bgcolor="#D5D9FA">
                <p>
                    <strong>Cliente:</strong>
                    <?=$data['cliente']['nombre'].' '. $data['cliente']['ape_pat'].' '. $data['cliente']['ape_mat'] ?>
                </p>
                <p style="margin-top: -8">
                    <strong>Domicilio:</strong>
                    <?=$data['cliente']['calle']?>
                </p>
                <p style="margin-top: -8">
                    Colonia:
                    <?=$data['cliente']['colonia']?> Edif:
                    <?=$data['cliente']['num_edificio']?> Depto:
                    <?=$data['cliente']['num_departamento']?>
                </p>
                <p style="margin-top: -8;">
                    <strong>Convenio:</strong>
                    <?=$data['cliente']['convenio']?> &nbsp; &nbsp;&nbsp;
                    <strong>Referencia:</strong>
                    <?=$data['cliente']['referencia']?> <br><br>
                </p>
            </td>
            <td colspan="2" style=" width: 40%" bgcolor="#88CDFF">
                <strong>Total a pagar:</strong> &nbsp; &nbsp;&nbsp; $
                <?=number_format(round($data['lectura']['total_a_pagar'],2), 2, ',', ' ')?><br>
                <strong>Periodo :</strong> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                <?php
                        $mes1=substr($data['lectura']['periodo'], 3, 2);
                        $mes2=substr($data['lectura']['periodo'], -7, 2);
                        $mes_l1=letra_mes2($mes1);  
                        $mes_l2=letra_mes2($mes2);    
                        //echo substr ($data['lectura']['periodo'], 0, 3) . substr($mes_l1, 0,3) . substr($data['lectura']['periodo'], 5, 9). substr( $mes_l2, 0,3) .substr( $data['lectura']['periodo'], 16, 13);
                        echo $data['lectura']['periodo'];
                ?><br>
                <strong>Pagar antes del: </strong> &nbsp;


                <strong>
                    <?=( $data['vencido'] ? 'Pago Inmediato' : $data['lectura']['fecha_limite']) ?>
                </strong>

            </td>

        </tr>
    </table>




    <table style="width: 100%; " border="0">
        <tr bgcolor="#5D41BD">
            <td>
                <strong> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                    &nbsp; &nbsp; &nbsp;&nbsp; &nbsp; &nbsp; &nbsp;Comprobante de consumo</strong> &nbsp; &nbsp; &nbsp;
                &nbsp; &nbsp;
            </td>
            <td colspan="2">&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
                <strong>Detalles de consumo</strong>
            </td>

        </tr>
        <tr>
            <td rowspan="9" style="width: 60%; vertical-align: top;">
                <!--inicio-->
                <?php
                  $final_img_path = user_foto . $data['ruta_img'];
                  // Fallback visual si el archivo no existe físicamente
                  if(!file_exists($final_img_path) || empty($data['ruta_img'])){
                      $final_img_path = img . 'default.jpg';
                  }
                ?>
                <table border="0" width="100%">
                    <tr>
                        <td style="text-align: center; padding-top: 5px;">
                             <img src="<?= $final_img_path ?>" height="210" width="298">
                        </td>
                    </tr>
                </table>
                <!--fin-->
            </td>
            <td style="width: 25%; height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Lectura inicial:</td>
            <td style="width: 15%; height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;"><?=round($data['lectura']['lectura_ini'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Lectura final:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;"><?=round($data['lectura']['lectura_fin'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Consumos litros:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;"><?=round($data['lectura']['consumos_litros'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Saldo favor:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;">$<?=number_format($data['saldofavor'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Adeudos:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;">$<?=number_format($data['adeudo'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Cargos adicionales:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;">$<?=number_format($data['lectura']['cargos_add'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Cuota admon:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;">$<?=number_format($data['lectura']['cuota_admin'], 2)?></td>
        </tr>
        <tr>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: left;">Precio del gas:</td>
            <td style="height: 16px; font-size: 11px; vertical-align: middle; text-align: right; padding-right: 2mm;">$<?=number_format($data['precio_gas'], 2)?></td>
        </tr>
        <tr>
            <td style="vertical-align: top; text-align: left; padding-top: 2px;">
                <h3 style="margin: 0; padding: 0; font-size: 14px;">Total a pagar</h3>
                <h6 style="margin: 0; padding: 0; font-size: 9px; font-weight: normal;">Pagar cantidad exacta con centavos</h6>
            </td>
            <td style="vertical-align: top; text-align: right; padding-right: 2mm; padding-top: 2px;">
                <h3 style="margin: 0; padding: 0; font-size: 14px;">$<?=number_format($data['total_real'], 2)?></h3>
                <h6 style="margin: 0; padding: 0; font-size: 9px; font-weight: normal;"><?=convertir($data['total_real'])?></h6>
            </td>
        </tr>

    </table>
    <strong>Historial</strong>
    <table border="0" style="width: 100%; font-size: 14px; border-collapse: collapse; text-align: center;">
        <tr style="background-color: #ECE9E9; font-weight: bold;">
            <td style="width: 15%; padding: 4px;">PERIODO</td>
            <td style="width: 12%; padding: 4px;">LEC. ANT</td>
            <td style="width: 12%; padding: 4px;">LEC. ACT</td>
            <td style="width: 10%; padding: 4px;">M3</td>
            <td style="width: 10%; padding: 4px;">LITROS</td>
            <td style="width: 13%; padding: 4px;">CONSUMO MES</td>
            <td style="width: 13%; padding: 4px;">PAGADO</td>
            <td style="width: 15%; padding: 4px;">TOTAL A PAGAR</td>
        </tr>


        <?=$data['historial']?>




    </table>


    <!----tabla end--->





    <?php
                    if($data['cliente']['id_cuenta']==1){//zaira
                ?>
    <!-- Sección: Opciones de pago ZAIRA -->
    <div style="text-align: center; font-weight: bold; font-size: 14px; margin-top: 10px; margin-bottom: 5px;">
        Opciones de pago
    </div>
    <table border="0" width="100%" style="width: 100%; font-size: 13px; border-collapse: collapse;">
        <colgroup>
            <col style="width: 33%;">
            <col style="width: 33%;">
            <col style="width: 34%;">
        </colgroup>
        <!-- Fila espaciadora invisible para forzar el ancho al 100% -->
        <tr style="height: 1px; line-height: 1px;">
            <td width="33%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="33%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="34%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
        <tr>
            <!-- OXXO -->
            <td style="width: 33%; text-align: center; vertical-align: middle; padding: 4px;">
                <img src="<?=img.'oxxo-logo.png' ?>" height="60" width="110">
            </td>
            <!-- BBVA -->
            <td style="width: 33%; text-align: center; vertical-align: middle; padding: 4px;">
                <img src="<?=img.'bbva-logo.png' ?>" height="60" width="140">
            </td>
            <!-- BANAMEX -->
            <td style="width: 34%; text-align: center; vertical-align: middle; padding: 4px;">
                <img src="<?=img.'banamex-logo.png' ?>" height="60" width="140">
            </td>
        </tr>
        <tr>
            <!-- Tarjetas Oxxo/Banamex/BBVA -->
            <td style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                BANAMEX (Tarjeta)<br>5204 1652 3117 3695<br><br>
                BBVA (Tarjeta)<br>4152 3139 3022 9138
            </td>
            <td style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                BANCOMER 1515561019<br>
                CLABE 012180015155610196
            </td>
            <td style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                BANAMEX 7012-3820248<br>
                CLABE 002180701238202486
            </td>
        </tr>
        <tr>
            <td colspan="3" style="text-align: center; padding-top: 10px;">
                <strong>Nombre: ZAIRA ABIGAIL VILLA GARCIA</strong>
            </td>
        </tr>
    </table>

    <?php    
                   }else{
                   ?>

    <!-- Sección: Opciones de pago LIZZETTE -->
    <div style="text-align: center; font-weight: bold; font-size: 14px; margin-top: 10px; margin-bottom: 5px;">
        Opciones de pago
    </div>
    <table border="0" width="100%" style="width: 100%; font-size: 13px; border-collapse: collapse;">
        <colgroup>
            <col style="width: 22%;">
            <col style="width: 26%;">
            <col style="width: 26%;">
            <col style="width: 26%;">
        </colgroup>
        <!-- Fila espaciadora invisible para forzar el ancho al 100% -->
        <tr style="height: 1px; line-height: 1px;">
            <td width="22%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="26%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="26%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
            <td width="26%" style="color: transparent;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        </tr>
        <!-- Fila de logos: col1=pago efectivo, col2=Banorte, col3=Banamex, col4=Scotiabank -->
        <tr>
            <!-- Pago en efectivo: Oxxo+Farmacias arriba, Seven abajo -->
            <td width="22%" style="text-align: center; vertical-align: middle; padding: 4px;">
                <table border="0" width="100%" style="border-collapse: collapse;">
                    <tr>
                        <td style="text-align: center; vertical-align: middle; padding-bottom: 4px;">
                            <img src="<?=img.'oxxo-logo.png' ?>" height="38" width="70">
                            &nbsp;
                            <img src="<?=img.'farmacias.jpg' ?>" height="38" width="70">
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; vertical-align: middle;">
                            <img src="<?=img.'seven.png' ?>" height="38" width="70">
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Banorte -->
            <td width="26%" style="text-align: center; vertical-align: middle; padding: 4px;">
                <img src="<?=img.'banorte.png' ?>" height="60" width="140">
            </td>

            <!-- Banamex -->
            <td width="26%" style="text-align: center; vertical-align: middle; padding: 4px;">
                <img src="<?=img.'banamex-logo.png' ?>" height="60" width="140">
            </td>

            <!-- Scotiabank -->
            <td width="26%" style="text-align: center; vertical-align: middle; padding: 4px;">
                <img src="<?=img.'scotiabank.jpg' ?>" height="60" width="140">
            </td>
        </tr>

        <!-- Fila: tarjetas / número de cuenta -->
        <tr>
            <td width="22%" style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                BANAMEX (Tarjeta)<br>5204 1658 6987 7799<br><br>
                SCOTIABANK (Tarjeta)<br>4043 1300 1323 7926
            </td>
            <td width="26%" style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                BANORTE 1202690242<br>CLABE 072180012026902426
            </td>
            <td width="26%" style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                BANAMEX 7018000005567271<br>CLABE 002180701855672716
            </td>
            <td width="26%" style="font-size: 11px; text-align: center; vertical-align: top; padding: 3px 2px;">
                SCOTIABANK 25604513049<br>CLABE 044180256045130490
            </td>
        </tr>

        <!-- Nombre titular -->
        <tr>
            <td colspan="4" style="text-align: center; padding-top: 5px;">
                <strong>Nombre: LIZZETTE VILLA GARCIA</strong>
            </td>
        </tr>
    </table>




    <?php
                     }
                     ?>


    <hr>
    <strong>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Horarios
        de atencion: lunes a viernes de 10:00 a 19:00 hrs y sábados de 10:00 a 15:00 hrs</strong>
    <hr>
    <strong>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <h1 style="color: red; text-align: center; vertical-align: center; align-items: center;">

            <?=( $data['vencido'] ? '¡Favor de aclarar sus pagos evite el corte! '  : '')?>
        </h1>
    </strong>



</page>