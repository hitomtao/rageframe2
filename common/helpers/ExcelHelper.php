<?php
namespace common\helpers;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use yii\web\NotFoundHttpException;

/**
 * Class ExcelHelper
 * @package common\helpers
 */
class ExcelHelper
{
    /**
     * 导出Excel
     *
     * @param array $list
     * @param array $header
     * @param string $filename
     * @param string $title
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportData($list = [], $header = [], $filename = '', $suffix = 'xlsx')
    {
        if (!is_array ($list) || !is_array ($header))
        {
            return false;
        }

        !$filename && $filename = time();

        // 初始化
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // 写入头部
        $hk = 1;
        foreach ($header as $k => $v)
        {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($hk) . '1', $v[0]);
            $hk += 1;
        }

        // 开始写入内容
        $column = 2;
        $size = ceil(count($list) / 500);
        for($i = 0; $i < $size; $i++)
        {
            $buffer = array_slice($list, $i * 500, 500);
            foreach($buffer as $row)
            {
                $span = 1;
                foreach($header as $key => $value)
                {
                    // 解析字段
                    $realData = self::formatting($header[$key], trim(self::formattingField($row, $value[1])));
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($span) . $column, $realData);
                    $span++;
                }

                $column++;
            }
        }

        // 直接输出下载
        switch ($suffix)
        {
            case 'xlsx' :
                $writer = new Xlsx($spreadsheet);
                header('Pragma:public');
                header("Content-Type:application/x-msexecl;name=\"{$filename}.xlsx\"");
                header("Content-Disposition:inline;filename=\"{$filename}.xlsx\"");
                $writer->save('php://output');
                break;

            case 'xls' :
                $writer = new Xls($spreadsheet);
                header('Pragma:public');
                header("Content-Type:application/x-msexecl;name=\"{$filename}.xls\"");
                header("Content-Disposition:inline;filename=\"{$filename}.xls\"");
                $writer->save('php://output');
                break;

            case 'Csv' :
                $writer = new Csv($spreadsheet);
                header('Pragma:public');
                header("Content-type:text/csv;");
                header("Content-Disposition:attachment; filename={$filename}.csv");
                $writer->save('php://output');
                exit();
                break;

            case 'Html' :
                $writer = new Html($spreadsheet);
                header('Pragma:public');
                header("Content-Type:text/html;");
                header("Content-Disposition:attachment;filename=\"{$filename}.{$suffix}\"");
                $writer->save('php://output');
                exit();
                break;
        }

        return true;
    }

    /**
     * 导入
     *
     * @param $filePath
     * @param int $startRow
     * @return array|mixed
     * @throws NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public static function import($filePath, $startRow = 1)
    {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        if (!$reader->canRead($filePath))
        {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
            // setReadDataOnly Set read data only 只读单元格的数据，不格式化 e.g. 读时间会变成一个数据等
            $reader->setReadDataOnly(true);
            if (!$reader->canRead($filePath))
            {
                throw new NotFoundHttpException('不能读取Excel');
            }
        }

        $spreadsheet = $reader->load($filePath);
        $sheetCount = $spreadsheet->getSheetCount();// 获取sheet的数量

        // 获取所有的sheet表格数据
        $excleDatas = [];
        $emptyRowNum = 0;
        for ($i = 0; $i < $sheetCount; $i++)
        {
            $currentSheet = $spreadsheet->getSheet($i);// 读取excel文件中的第一个工作表
            $allColumn = $currentSheet->getHighestColumn();// 取得最大的列号
            $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行

            $arr = [];
            for ($currentRow = $startRow; $currentRow <= $allRow; $currentRow++)
            {
                // 从第A列开始输出
                for ($currentColumn = 'A'; $currentColumn <= $allColumn; $currentColumn++)
                {
                    $val = $currentSheet->getCellByColumnAndRow(ord($currentColumn) - 65, $currentRow)->getValue();
                    $arr[$currentRow][] = trim($val);
                }
                $arr[$currentRow] = array_filter($arr[$currentRow]);
                // 统计连续空行
                if (empty($arr[$currentRow]) && $emptyRowNum <= 50)
                {
                    $emptyRowNum++ ;
                }
                else
                {
                    $emptyRowNum = 0;
                }
                // 防止坑队友的同事在excel里面弄出很多的空行，陷入很漫长的循环中，设置如果连续超过50个空行就退出循环，返回结果
                // 连续50行数据为空，不再读取后面行的数据，防止读满内存
                if ($emptyRowNum > 50)
                {
                    break;
                }
            }
            $excleDatas[$i] = $arr; // 多个sheet的数组的集合
        }

        // 这里我只需要用到第一个sheet的数据，所以只返回了第一个sheet的数据
        $returnData = $excleDatas ? array_shift($excleDatas) : [];

        // 第一行数据就是空的，为了保留其原始数据，第一行数据就不做array_fiter操作；
        $returnData = $returnData && isset($returnData[$startRow]) && !empty($returnData[$startRow])  ? array_filter($returnData) : $returnData;
        return $returnData;
    }

    /**
     * 格式化内容
     *
     * @param array $array 头部规则
     * @return false|mixed|null|string 内容值
     */
    protected static function formatting(array $array, $value)
    {
        switch ($array[2])
        {
            // 文本
            case 'text' :
                return $value;
                break;
            // 日期
            case  'date' :
                return date($array[3], $value);
                break;
            // 选择框
            case  'selectd' :
                return  isset($array[3][$value]) ? $array[3][$value] : null ;
                break;
            // 匿名函数
            default :

                break;
        }

        return null;
    }

    /**
     * 解析字段
     *
     * @param $row
     * @param $field
     * @return mixed
     */
    protected static function formattingField($row, $field)
    {
        $newField = explode('.', $field);
        if(count($newField) == 1)
        {
            return $row[$field];
        }

        foreach ($newField as $item)
        {
            if(isset($row[$item]))
            {
                $row = $row[$item];
            }
            else
            {
                break;
            }
        }

        return $row;
    }
}