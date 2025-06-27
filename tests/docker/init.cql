-- Create test keyspace
CREATE KEYSPACE IF NOT EXISTS test_keyspace
WITH REPLICATION = {
    'class': 'SimpleStrategy',
    'replication_factor': 1
};

-- Use the test keyspace
USE test_keyspace;

-- Create a sample table for testing
CREATE TABLE IF NOT EXISTS test_users (
    id UUID PRIMARY KEY,
    name text,
    email text,
    age int,
    created_at timestamp
);

-- Create another table for more complex testing
CREATE TABLE IF NOT EXISTS test_posts (
    user_id UUID,
    post_id UUID,
    title text,
    content text,
    tags set<text>,
    metadata map<text, text>,
    created_at timestamp,
    PRIMARY KEY (user_id, post_id)
) WITH CLUSTERING ORDER BY (post_id DESC);

-- Create table for migration testing
CREATE TABLE IF NOT EXISTS migrations (
    id UUID PRIMARY KEY,
    migration text,
    batch int
);

CREATE INDEX IF NOT EXISTS migrations_migration_idx ON migrations (migration);
CREATE INDEX IF NOT EXISTS migrations_batch_idx ON migrations (batch); 