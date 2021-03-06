<?php
use yii\helpers\Html;
use common\enums\StatusEnum;
?>

<div class="form-group">
    <?php echo Html::label($row['title'], $row['name'], ['class' => 'control-label demo']);?>
    <?php if($row['is_hide_remark'] != StatusEnum::ENABLED){ ?>
        (<?php echo $row['remark']?>)
    <?php } ?>
    <div class="input-group">
        <?php echo Html::input('text','config[' . $row['name'] . ']', $row['value'], ['class' => 'form-control','id' => $row['id']]);?>
        <span class="input-group-btn">
            <span class="btn btn-white" onclick="createKey(<?php echo $row['extra']?>, <?php echo $row['id']?>)">生成新的</span>
        </span>
    </div>
</div>