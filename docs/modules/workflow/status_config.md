- The workflow is status-centric.
- The statuses are configurable in admin panel.
- **Superseded by `design.md`** — this file is kept for reference only. The design doc is the canonical source.

#### **Status Configuration**
```ts
class StatusConfig {
    flow: string
    code: string
    label: string
    pic: string[]
    notifications: object
    position: int
    commentTags: object
    prompt: string
    kanbanCode: string
    isActive: boolean
}
```

**Removed fields (see `design.md` §4.2 for rationale):**
- `permissions` — replaced by AuthZ capabilities on transitions (§6.1)
- `nextStatuses` — transitions table is the single source of truth for edges (§6)

#### **Database Schema**

**PostgreSQL SQL:**
```sql
CREATE TABLE workflow_status_configs (
    id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    flow VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL,
    pic JSONB NULL,
    notifications JSONB NULL,
    position INTEGER NOT NULL DEFAULT 0,
    comment_tags JSONB NULL,
    prompt TEXT NULL,
    kanban_code VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT unique_flow_code UNIQUE (flow, code)
);

CREATE INDEX idx_flow ON workflow_status_configs (flow);
CREATE INDEX idx_flow_active ON workflow_status_configs (flow, is_active);
CREATE INDEX idx_kanban_code ON workflow_status_configs (kanban_code);
```

**Laravel Migration:**
```php
Schema::create('workflow_status_configs', function (Blueprint $table) {
    $table->id();
    $table->string('flow');
    $table->string('code');
    $table->string('label');
    $table->json('pic')->nullable();
    $table->json('notifications')->nullable();
    $table->integer('position')->default(0);
    $table->json('comment_tags')->nullable();
    $table->text('prompt')->nullable();
    $table->string('kanban_code')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['flow', 'code'], 'unique_flow_code');
    $table->index('flow', 'idx_flow');
    $table->index(['flow', 'is_active'], 'idx_flow_active');
    $table->index('kanban_code', 'idx_kanban_code');
});
```

**Schema Notes:**
- `flow` and `code` form a unique composite key to prevent duplicate status codes per flow type
- JSON columns store structured data (pic array, notifications, commentTags)
- `position` determines ordering within the same flow
- `kanban_code` is indexed for quick lookups in kanban views
- `is_active` allows soft disabling of statuses without deletion
- Uses `$table->id()` for bigint PK per BLB convention
- Table name uses `workflow_` prefix per Core module naming convention
