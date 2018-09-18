<?php

namespace App\Models;

class Page
{
    public $firstRow; // 起始行数
    public $listRows; // 列表每页显示行数
    public $parameter; // 分页跳转时要带的参数
    public $totalRows; // 总行数
    public $totalPages; // 分页总页面数
    public $rollPage = 6; // 分页栏每页显示的页数

    private $p       = 'page'; //分页参数名
    private $nowPage = 1;

    /**
     * 架构函数
     * @param int $totalRows  总的记录数
     * @param int $listRows  每页显示记录数
     * @param array $parameter  分页跳转的参数
     */
    public function __construct($totalRows, $listRows = 20, $parameter = array())
    {
        /* 基础设置 */
        $this->totalRows = $totalRows; //设置总记录数
        $this->listRows  = $listRows; //设置每页显示行数
        $this->parameter = empty($parameter) ? $_GET : $parameter;
        $this->nowPage   = empty($parameter[$this->p]) ? 1 : intval($parameter[$this->p]);
        $this->nowPage   = $this->nowPage > 0 ? $this->nowPage : 1;
        $this->firstRow  = $this->listRows * ($this->nowPage - 1);
    }

    /**
     * 组装分页链接
     * @return array
     */
    public function show()
    {
        $pages = [];
        if (0 == $this->totalRows) {
            return [];
        }

        /* 计算分页信息 */
        $this->totalPages = ceil($this->totalRows / $this->listRows); //总页数
        if (!empty($this->totalPages) && $this->nowPage > $this->totalPages) {
            $this->nowPage = $this->totalPages;
        }

        /* 计算分页临时变量 */
        $now_cool_page      = $this->rollPage / 2;
        $now_cool_page_ceil = ceil($now_cool_page);

        //第一页
        if ($this->totalPages > $this->rollPage && ($this->nowPage - $now_cool_page) >= 1) {
            $pages['首页'] = 1;
        }

        //上一页
        $up_row  = $this->nowPage - 1;
        $up_row > 0 && $pages['上页'] = $this->nowPage - 1;

        //数字页码
        for ($i = 1; $i <= $this->rollPage; $i++) {
            if (($this->nowPage - $now_cool_page) <= 0) {
                $page = $i;
            } elseif (($this->nowPage + $now_cool_page - 1) >= $this->totalPages) {
                $page = $this->totalPages - $this->rollPage + $i;
            } else {
                $page = $this->nowPage - $now_cool_page_ceil + $i;
            }

            if ($page > 0 && $page != $this->nowPage) {
                if ($page <= $this->totalPages) {
                    $pages["$page"] = $page;
                } else {
                    break;
                }
            } else {
                if ($page > 0 && 1 != $this->totalPages) {
                    $pages["$page"] = $page;
                }
            }
        }

        //下一页
        $down_row  = $this->nowPage + 1;
        $down_row <= $this->totalPages && $pages['下页'] = $this->nowPage + 1;

        //最后一页
        if ($this->totalPages > $this->rollPage && ($this->nowPage + $now_cool_page) < $this->totalPages) {
            $pages['尾页'] = $this->totalPages;
        }

        return $pages;
    }
}
