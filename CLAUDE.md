# naibabiji-b2b-product-showcase

B2B 产品目录插件，替代 WooCommerce，为制造商/出口商/批发商提供产品展示和 RFQ 询价功能。已发布到 WordPress.org，100+ 活跃安装。

## 架构

- 单例入口 `Naibabiji_B2B_Product_Showcase`，位于主文件 `naibabiji-b2b-product-showcase.php`
- `includes/` — 18 个类文件，各管一个职责（设置、前端、AJAX、AI、询盘等）
- `templates/` — 前端模板，支持主题覆盖
- `assets/css/` + `assets/js/` — 手写 CSS + vanilla JS（依赖 jQuery），无构建工具
- CPT: `naibb2pr_products`，URL slug `/products/`
- 分类: `naibb2pr_product_category`、`naibb2pr_product_tag`
- 自定义 DB 表: `{prefix}naibb2pr_ai_leads`（询盘数据，含 `inquiry_type` 和 `inquiry_data` 列）
- 产品 meta 使用分组存储（`_naibabiji_b2b_product_data` key）
- 版本升级在 `upgrade_database()` 中处理，带版本号比对

## 关键约束

**向后兼容 — 绝对不能破坏现有用户数据。** 100+ 活跃安装。新功能必须叠加式添加，不修改已有 option key、DB 列、模板输出。改变默认行为必须是 opt-in（新 setting 或 filter）。

## 开发规则

1. 先想清楚再动手 — 不确定就问，多种方案先列出来
2. 只写必要代码 — 不加需求外功能，不创建一次性抽象，不处理不可能发生的错误
3. 精准修改 — 只改任务相关代码，不顺手改周边代码/注释/格式，匹配现有代码风格

## 用户角色

用户为非技术人员，负责提需求和测试。沟通时用简单语言，提供清晰的测试步骤。不期望用户执行 CLI 命令。

## 测试

本地用 Local WP 浏览器测试。必要时上传到线上服务器测试。

## 多 AI 协作

多个 AI 工具参与本项目的开发和代码审核。通过 Git 协作：
- 每次提交信息写明「改了什么、为什么改」，确保其他 AI 看懂上下文
- 不留下半成品代码（如注释掉的逻辑、未提交的 TODO）
- 代码审核基于相同规则：功能正确、向后兼容、匹配现有风格
- 发现问题直接在代码里改，不在注释里写建议

## 网络

本地 GitHub 网络不稳定时，使用代理 127.0.0.1:10808。本项目 git 已配置好代理，若代理未启动或变更，运行：

```bash
git config http.proxy http://127.0.0.1:10808
git config https.proxy http://127.0.0.1:10808
```

GitHub CLI (`gh`) 也需单独配置代理（设置环境变量）：

```bash
# Windows CMD
set HTTP_PROXY=http://127.0.0.1:10808
set HTTPS_PROXY=http://127.0.0.1:10808
```
