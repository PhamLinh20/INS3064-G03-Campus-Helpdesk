-- ============================================================================
-- CAMPUS HELPDESK – VERTICAL SLICE STATISTICAL QUERIES (stats.sql)
-- Target Database: campus_helpdesk_midterm
-- Slice: classrooms (Main Table) & assets (Related Table)
-- ============================================================================

USE `campus_helpdesk_midterm`;

-- ----------------------------------------------------------------------------
-- QUERY 1: INNER JOIN
-- Purpose: Retrieve all classrooms along with their currently deployed assets.
-- Business Logic: Correlates active equipment with their physical room locations.
-- Rooms without any assigned assets will be excluded automatically.
-- ----------------------------------------------------------------------------
SELECT 
    c.room_number,
    c.building,
    c.floor,
    a.asset_code,
    a.asset_name,
    a.status AS asset_status
FROM classrooms c
INNER JOIN assets a ON c.classroom_id = a.classroom_id
ORDER BY c.room_number ASC, a.asset_code ASC;

-- ----------------------------------------------------------------------------
-- QUERY 2: LEFT JOIN (With zero-count verification)
-- Purpose: Count the total number of assets assigned to each classroom.
-- Business Logic: Identifies newly established or vacant rooms with zero assets 
-- to support facility procurement and equipment re-allocation.
-- Key Mechanism: COUNT(a.asset_id) correctly outputs 0 for unequipped rooms.
-- ----------------------------------------------------------------------------
SELECT 
    c.classroom_id,
    c.room_number,
    c.building,
    c.floor,
    COUNT(a.asset_id) AS total_assets
FROM classrooms c
LEFT JOIN assets a ON c.classroom_id = a.classroom_id
GROUP BY c.classroom_id, c.room_number, c.building, c.floor
ORDER BY total_assets ASC, c.room_number ASC;

-- ----------------------------------------------------------------------------
-- QUERY 3: GROUP BY + HAVING
-- Purpose: Identify multi-equipment classrooms having 2 or more assigned assets.
-- Business Logic: Filters high-density rooms requiring regular maintenance 
-- schedules and calculates the cumulative fault report count per room.
-- ----------------------------------------------------------------------------
SELECT 
    c.classroom_id,
    c.room_number,
    c.building,
    COUNT(a.asset_id) AS total_assets,
    SUM(a.fault_count) AS total_fault_reports
FROM classrooms c
INNER JOIN assets a ON c.classroom_id = a.classroom_id
GROUP BY c.classroom_id, c.room_number, c.building
HAVING COUNT(a.asset_id) >= 2
ORDER BY total_assets DESC, total_fault_reports DESC;