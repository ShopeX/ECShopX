# 数据辞典

数据库表数量: {{ count($tables) }}个

@foreach($tables as $table)
- [{{ $table['name'] }}({{ $table['comment'] }})](#table-{{ $table['orig_name'] }})
@endforeach


@foreach($tables as $table )

<a name="table-{{ $table['orig_name'] }}"></a>
## {{ $table['name'] }}

> **{{ $table['comment'] }}**

- 表结构

| ColumnName | Comment | Type | Not null | Default | Autoincrement |
| ------------- |-------------|-------------|-------------|-------------|-------------|
@foreach($table['columns'] as $column )
| {{ $column['name'] }}  | {{ $column['comment'] }}| {{ $column['type'] }} | {{ $column['notnull'] }} | {{ $column['default'] }} | {{ $column['autoincrement'] }} |
@endforeach

- 索引

| Key name | Column names | Unique |
| ------------- |-------------|-------------|
@foreach($table['indexes'] as $index )
| {{ $index['name'] }} | {{ $index['columns'] }} | {{ $index['isUnique'] }} |
@endforeach
@endforeach
