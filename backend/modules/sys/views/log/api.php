<?php
use yii\helpers\Url;
use yii\widgets\LinkPager;

$this->title = 'Api日志';
$this->params['breadcrumbs'][] = ['label' => $this->title];
?>

<div class="wrapper wrapper-content animated fadeInRight">
    <div class="tabs-container">
        <?php echo $this->render('_nav', [
            'type' => 'api'
        ]) ?>
        <div class="tab-content">
            <div class="tab-pane active">
                <div class="panel-body">
                    <div class="col-sm-12">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>提交方法</th>
                                <th>用户</th>
                                <th>模块</th>
                                <th>控制器方法</th>
                                <th>Url</th>
                                <th>IP</th>
                                <th>地区</th>
                                <th>状态码</th>
                                <th>创建时间</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach($models as $model){ ?>
                                <tr id = <?php echo $model->id; ?>>
                                    <td><?php echo $model->id; ?></td>
                                    <td><?php echo $model->method; ?></td>
                                    <td><?= isset($model->member->username) ? $model->member->username : '游客' ?></td>
                                    <td><?php echo $model->module; ?></td>
                                    <td><?php echo $model->controller; ?>/<?php echo $model->action; ?></td>
                                    <td><?php echo $model->url; ?></td>
                                    <td><?php echo long2ip($model->ip); ?></td>
                                    <td>
                                        <?php
                                        if (!empty($model['ip']) && ($ipData = \Zhuzhichao\IpLocationZh\Ip::find(long2ip($model['ip'])))) {
                                            echo $ipData[0] . '·' . $ipData[1] . '·' . $ipData[2];
                                        } else {
                                            echo '本地';
                                        } ?>
                                    </td>
                                    <td>
                                        <?php if($model->error_code < 300){ ?>
                                            <span class="label label-primary"><?php echo $model->error_code; ?></span>
                                        <?php }else{ ?>
                                            <span class="label label-danger"><?php echo $model->error_code; ?></span>
                                        <?php } ?>
                                    </td>
                                    <td><?php echo Yii::$app->formatter->asDatetime($model->created_at); ?></td>
                                    <td>
                                        <a href="<?php echo Url::to(['api-view','id' => $model->id])?>" data-toggle='modal' data-target='#ajaxModalLg'><span class="btn btn-info btn-sm">查看详情</span></a>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-sm-12">
                                <?php echo LinkPager::widget([
                                    'pagination' => $pages,
                                    'maxButtonCount' => 5,
                                    'firstPageLabel' => "首页",
                                    'lastPageLabel' => "尾页",
                                    'nextPageLabel' => "下一页",
                                    'prevPageLabel' => "上一页",
                                ]);?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>