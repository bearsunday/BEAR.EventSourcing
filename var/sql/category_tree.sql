WITH RECURSIVE category_tree AS (
    SELECT
        id,
        name,
        parent_id,
        sort_no,
        1 as level,
        CAST(LPAD(sort_no, 5, '0') AS CHAR(1000)) as path
    FROM category
    WHERE parent_id IS NULL

    UNION ALL

    SELECT
        c.id,
        c.name,
        c.parent_id,
        c.sort_no,
        ct.level + 1,
        CONCAT(ct.path, '-', LPAD(c.sort_no, 5, '0'))
    FROM category c
    INNER JOIN category_tree ct ON c.parent_id = ct.id
)
SELECT id, name, parent_id, sort_no, level
FROM category_tree
ORDER BY path
