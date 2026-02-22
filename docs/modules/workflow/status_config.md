- The workflow is status-centric.
- The statues are configurable in admin panel.

#### **Status Configuration**
```ts
class StatusConfig {
    entity: string
    code: string
    label: string
    permissions: object
    pic: string[]
    notifications: object
    nextStatuses: string[]
    position: int
    commentTags: object
    prompt: string
    kanbanCode: string
    isActive: boolean
}
```

#### **Database Schema**

**PostgreSQL SQL:**
```sql
CREATE TABLE status_configs (
    id INTEGER PRIMARY KEY GENERATED ALWAYS AS IDENTITY,
    entity VARCHAR(255) NOT NULL,
    code VARCHAR(255) NOT NULL,
    label VARCHAR(255) NOT NULL,
    permissions JSONB NULL,
    pic JSONB NULL,
    notifications JSONB NULL,
    next_statuses JSONB NULL,
    position INTEGER NOT NULL DEFAULT 0,
    comment_tags JSONB NULL,
    prompt TEXT NULL,
    kanban_code VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    CONSTRAINT unique_entity_code UNIQUE (entity, code)
);

CREATE INDEX idx_entity ON status_configs (entity);
CREATE INDEX idx_entity_active ON status_configs (entity, is_active);
CREATE INDEX idx_kanban_code ON status_configs (kanban_code);
```

**Laravel Migration:**
```php
Schema::create('status_configs', function (Blueprint $table) {
    $table->increments('id');
    $table->string('entity');
    $table->string('code');
    $table->string('label');
    $table->json('permissions')->nullable();
    $table->json('pic')->nullable();
    $table->json('notifications')->nullable();
    $table->json('next_statuses')->nullable();
    $table->integer('position')->default(0);
    $table->json('comment_tags')->nullable();
    $table->text('prompt')->nullable();
    $table->string('kanban_code')->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->unique(['entity', 'code'], 'unique_entity_code');
    $table->index('entity', 'idx_entity');
    $table->index(['entity', 'is_active'], 'idx_entity_active');
    $table->index('kanban_code', 'idx_kanban_code');
});
```

**Schema Notes:**
- `entity` and `code` form a unique composite key to prevent duplicate status codes per entity type
- JSON columns store structured data (permissions, pic array, notifications, nextStatuses array, commentTags)
- `position` determines ordering within the same entity
- `kanban_code` is indexed for quick lookups in kanban views
- `is_active` allows soft disabling of statuses without deletion

