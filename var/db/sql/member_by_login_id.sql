SELECT
    id,
    name,
    login_id,
    department,
    sort_no,
    two_factor_auth_enabled,
    create_date,
    update_date,
    login_date,
    authority_id
FROM dtb_member
WHERE login_id = :loginId
LIMIT 1
