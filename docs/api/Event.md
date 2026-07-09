# Event API 文件

Base URL: `/api/event/{action}`

所有請求方法皆為 `GET`。回傳格式統一為：

```json
{
  "code": 1,      // 1 = 成功, 0 = 失敗
  "msg": "success",
  "time": 1720512000,
  "data": {}
}
```

---

## 1. 取得體育分類列表

取得所有啟用中的體育分類（如：籃球、足球等）。

- **Endpoint**: `GET /api/event/getCategoryList`
- **權限**: 免登入

### 請求參數

無

### 回傳 data 欄位

| 欄位  | 型別   | 說明     |
|-------|--------|----------|
| id    | int    | 分類 ID  |
| title | string | 分類名稱 |

### 回傳範例

```json
{
  "code": 1,
  "msg": "success",
  "time": 1720512000,
  "data": [
    { "id": 1, "title": "籃球" },
    { "id": 2, "title": "足球" }
  ]
}
```

---

## 2. 取得賽事列表

依時間區間、分類、分頁查詢賽事列表。

- **Endpoint**: `GET /api/event/getEventList`
- **權限**: 免登入

### 請求參數

| 參數  | 型別 | 必填 | 預設值               | 說明                                                                 |
|-------|------|------|----------------------|----------------------------------------------------------------------|
| cid   | int  | 否   | 0                     | 體育分類 ID（對應 `getCategoryList` 的 `id`）。0 或不傳 = 不篩選分類 |
| start | int  | 否   | 今天 00:00:00 的時間戳 | 查詢區間起始時間（Unix Timestamp）                                   |
| end   | int  | 否   | start 起算 +2 天的 23:59:59 | 查詢區間結束時間（Unix Timestamp）                             |
| page  | int  | 否   | 1                     | 分頁頁碼                                                              |

**時間邏輯限制**：
- 若未帶 `start`，預設為「今天凌晨 00:00:00」。
- 若未帶 `end`，預設為「`start` 當天 23:59:59 再加 2 天」（共 3 天區間）。
- `end` 最多只能到「未來 30 天」，超過會被強制縮限。
- 每頁固定 25 筆。

### 回傳 data 欄位

| 欄位         | 型別   | 說明                                   |
|--------------|--------|----------------------------------------|
| total        | int    | 符合條件的總筆數                       |
| current_page | int    | 目前頁碼                               |
| last_page    | int    | 最後頁碼                               |
| has_more     | bool   | 是否還有下一頁                         |
| time_range   | object | 本次查詢實際使用的時間區間（含格式化字串） |
| items        | array  | 賽事列表（見下表）                     |

`items` 內每筆賽事欄位：

| 欄位               | 型別   | 說明                       |
|--------------------|--------|----------------------------|
| id                 | int    | 賽事 ID                    |
| event_category_id  | int    | 所屬體育分類 ID             |
| starttime          | int    | 開賽時間（Unix Timestamp） |
| guests             | string | 客場隊伍名稱                |
| master             | string | 主場隊伍名稱                |
| guests_refund      | string | 客場讓分盤口                |
| master_refund      | string | 主場讓分盤口                |
| bigscore           | string | 大小分盤口                  |
| guests_score       | string | 客場比分（未開賽/未結算可能為空） |
| master_score       | string | 主場比分（未開賽/未結算可能為空） |

### 回傳範例

```json
{
  "code": 1,
  "msg": "success",
  "time": 1720512000,
  "data": {
    "total": 42,
    "current_page": 1,
    "last_page": 2,
    "has_more": true,
    "time_range": {
      "start": "1720483200 (2026-07-09 00:00:00)",
      "end": "1720656000 (2026-07-11 23:59:59)"
    },
    "items": [
      {
        "id": 101,
        "event_category_id": 2,
        "starttime": 1720512000,
        "guests": "客隊A",
        "master": "主隊B",
        "guests_refund": "+1.5",
        "master_refund": "-1.5",
        "bigscore": "2.5",
        "guests_score": "",
        "master_score": ""
      }
    ]
  }
}
```

### 請求範例

```
GET /api/event/getEventList?cid=2&start=1720483200&end=1720656000&page=1
```
