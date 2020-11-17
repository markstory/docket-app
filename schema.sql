CREATE TABLE IF NOT EXISTS users (
    id int not null auto_increment primary key,
    email varchar(255) not null,
    password varchar(255) not null,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp
);

-- Could add organizations and teams. Then projects would be owned
-- by teams inside organizations.

CREATE TABLE IF NOT EXISTS projects (
    id int not null auto_increment primary key,
    user_id int not null,
    name varchar(255) not null,
    slug varchar(255) not null,
    color varchar(6) not null,
    favorite boolean not null default 0,
    archived boolean not null default 0,
    ranking int not null default 0,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (user_id) references users(id)
);

CREATE TABLE IF NOT EXISTS todo_items (
    id int not null auto_increment primary key,
    project_id integer not null,
    title text,
    body text,
    due_on date,
    child_order integer not null default 0,
    day_order integer not null default 0,
    completed boolean not null default 0,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (project_id) references projects(id)
);

CREATE TABLE IF NOT EXISTS todo_comments (
    id int not null auto_increment primary key,
    todo_item_id integer not null,
    user_id integer not null,
    body text,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (user_id) references users(id),
    foreign key (todo_item_id) references todo_items(id)
);

CREATE TABLE IF NOT EXISTS todo_subtasks (
    id int not null auto_increment primary key,
    todo_item_id integer not null,
    title text,
    body text,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (todo_item_id) references todo_items(id)
);

CREATE TABLE todo_labels (
    id int not null auto_increment primary key,
    project_id int not null,
    label varchar(50) not null,
    color char(6) not null,
    created timestamp default current_timestamp,
    modified timestamp default current_timestamp on update current_timestamp,
    foreign key (project_id) references projects(id)
);

CREATE TABLE todo_items_todo_labels (
    todo_item_id int not null,
    todo_label_id int not null,
    foreign key (todo_item_id) references todo_items(id),
    foreign key (todo_label_id) references todo_labels(id),
    primary key (todo_item_id, todo_label_id)
);
