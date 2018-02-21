<?php
use yii\helpers\Url;
?>
<? if( 0 ){ ?>
<script type='text/javascript'><?}?>

<? switch($section) : case 'onload' : ?>

$(function () {

    $('#files').on('change', function (e) {
        $('#submitButton').removeClass('hide');
    });

    $('#form').on('beforeSubmit', function (e) {

        $('#form').hide();
        $('#progress').removeClass('hide');
        
        $.ajax({
            url: '<?= Url::to( [ 'upload' ] )?>',
            type: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false
        })
            .done(function (result) {
                if( result != 0){
                    process();
                }
            })
        ;
    }).on('submit', function (e) {
        e.preventDefault();
    });
    ;


});

function process(){
    
    $.ajax({
        url: '<?= Url::to( [ 'process' ] )?>',
    })
    .done(function (result) {

        $('#progress .progress-bar').text( result.progress + '%', ).css({
            width: result.progress + '%',
        });

        if( result && result.progress < 100 ){
            process();
        }
        else{
            window.location.href = '<?= Url::to( [ 'index' ] )?>';
        }

        
    })
}

<? break; case 'crud' : ?>

<? break; endswitch; ?>

<? if( 0 ){ ?></script><? } ?>