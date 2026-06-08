-- Migration 003: IT department dummy data (assets + licenses)

INSERT INTO `it_assets` (asset_name, asset_type, brand, model, serial_number, purchase_date, warranty_expiry, assigned_to, status, location, notes, created_by) VALUES
('Admin Workstation',       'Laptop',   'Dell',     'XPS 15 9520',        'DL-XPS-00112', '2023-02-14', '2026-02-14', 1,  'assigned',    'Office Floor 1, Desk 1',  'Primary machine for admin user',               1),
('Manager Laptop',          'Laptop',   'Apple',    'MacBook Pro 14" M2', 'AP-MBP-00234', '2023-05-20', '2026-05-20', 3,  'assigned',    'Office Floor 1, Desk 3',  'macOS Ventura, 32GB RAM',                      1),
('Dev Laptop #1',           'Laptop',   'Lenovo',   'ThinkPad X1 Carbon', 'LN-X1C-00345', '2022-11-08', '2025-11-08', 4,  'assigned',    'Office Floor 2, Desk 7',  'Ubuntu 22.04 LTS',                             1),
('BD Laptop',               'Laptop',   'HP',       'EliteBook 840 G9',   'HP-EBK-00456', '2023-08-01', '2026-08-01', 5,  'assigned',    'Office Floor 2, Desk 12', '16GB RAM, 512GB SSD',                          1),
('Marketing MacBook',       'Laptop',   'Apple',    'MacBook Air M2',     'AP-MBA-00567', '2023-09-15', '2026-09-15', 6,  'assigned',    'Office Floor 3, Desk 2',  'Used for design and content creation',         1),
('Marketing Laptop #2',     'Laptop',   'Dell',     'Latitude 5540',      'DL-LAT-00678', '2022-06-30', '2025-06-30', 7,  'assigned',    'Office Floor 3, Desk 4',  'Windows 11 Pro',                               1),
('HR Laptop',               'Laptop',   'HP',       'ProBook 450 G10',    'HP-PRB-00789', '2023-01-20', '2026-01-20', 8,  'assigned',    'Office Floor 1, Desk 8',  '',                                             1),
('Finance Laptop',          'Laptop',   'Lenovo',   'ThinkPad E15',       'LN-E15-00890', '2023-03-10', '2026-03-10', 10, 'assigned',    'Office Floor 1, Desk 10', 'Accounting software installed',                1),
('Spare Laptop',            'Laptop',   'Dell',     'Inspiron 15',        'DL-INS-00991', '2021-04-05', '2024-04-05', NULL,'available',  'IT Storage Room',         'Warranty expired — available as spare',        1),
('Main Server',             'Server',   'Dell',     'PowerEdge R740',     'DL-SVR-10001', '2021-09-01', '2024-09-01', NULL,'available',  'Server Room',             'Primary application server, 128GB RAM',        1),
('Backup Server',           'Server',   'HP',       'ProLiant DL380 Gen10','HP-SVR-10002','2020-11-15', '2023-11-15', NULL,'maintenance','Server Room',             'Under maintenance — RAM upgrade in progress',  1),
('Reception Monitor',       'Monitor',  'Samsung',  '27" Odyssey',        'SS-MON-20011', '2022-07-22', '2025-07-22', NULL,'available',  'Reception Area',          '27 inch curved 144Hz',                         1),
('Dev Monitor #1',          'Monitor',  'LG',       '27UK850-W 4K',       'LG-MON-20012', '2023-02-14', '2026-02-14', 4,  'assigned',    'Office Floor 2, Desk 7',  '4K USB-C display',                             1),
('Office Printer',          'Printer',  'HP',       'LaserJet Pro M428',  'HP-PRN-30001', '2022-03-01', '2025-03-01', NULL,'available',  'Office Floor 1, Print Bay','Network printer — all floors',                 1),
('Finance Printer',         'Printer',  'Brother',  'HL-L2350DW',         'BR-PRN-30002', '2021-08-10', '2024-08-10', 10, 'assigned',    'Office Floor 1, Desk 10', 'Dedicated to finance department',              1),
('Core Router',             'Router',   'Cisco',    'RV340 Dual WAN',     'CS-RTR-40001', '2021-01-15', '2024-01-15', NULL,'available',  'Server Room',             'Primary internet gateway, dual WAN failover',  1),
('Network Switch',          'Switch',   'Cisco',    'SG350-28 Managed',   'CS-SWT-40002', '2021-01-15', '2024-01-15', NULL,'available',  'Server Room',             '28-port managed PoE switch',                   1),
('Android Work Phone',      'Phone',    'Samsung',  'Galaxy S23',         'SS-PH-50001',  '2023-06-01', '2025-06-01', 1,  'assigned',    'Mobile',                  'Company sim card installed',                   1),
('iPad Pro',                'Tablet',   'Apple',    'iPad Pro 12.9" M2',  'AP-TAB-60001', '2023-04-18', '2026-04-18', NULL,'available',  'IT Storage Room',         'Available for demo and presentations',         1),
('Old Desktop PC',          'Computer', 'Dell',     'OptiPlex 7060',      'DL-OPT-70001', '2018-05-10', '2021-05-10', NULL,'retired',    'IT Storage Room',         'Decommissioned — replaced by laptops',         1);

INSERT INTO `it_licenses` (software_name, vendor, license_key, license_type, seats, seats_used, purchase_date, expiry_date, cost, status, notes, created_by) VALUES
('Microsoft 365 Business Premium', 'Microsoft',  'M365-BPREM-XXXX-XXXX-0001', 'subscription', 15, 13, '2024-01-01', '2026-12-31', 8750.00,  'active',  'Includes Teams, SharePoint, OneDrive, Exchange',         1),
('Adobe Creative Cloud',           'Adobe',       'ADCC-TEAM-XXXX-XXXX-0002',  'subscription', 5,  3,  '2024-06-25', '2026-06-25', 4200.00,  'active',  'Marketing team — Photoshop, Illustrator, Premiere',      1),
('GitHub Enterprise',              'GitHub',      'GHE-CLOUD-XXXX-XXXX-0003',  'subscription', 10, 5,  '2025-01-15', '2027-01-15', 6500.00,  'active',  'Source control for all development projects',            1),
('Slack Business+',                'Slack',       'SLK-BIZ-XXXX-XXXX-0004',    'subscription', 20, 15, '2025-09-30', '2026-09-30', 3200.00,  'active',  'Primary team communication platform',                    1),
('JetBrains All Products',         'JetBrains',   'JB-ALL-XXXX-XXXX-0005',     'subscription', 5,  4,  '2025-07-01', '2026-07-01', 2800.00,  'active',  'IntelliJ IDEA, WebStorm, DataGrip — dev team',          1),
('Zoom Pro',                       'Zoom',        'ZM-PRO-XXXX-XXXX-0006',     'subscription', 10, 7,  '2025-11-30', '2026-11-30', 1800.00,  'active',  'Video conferencing for client meetings',                  1),
('Atlassian Jira Software',        'Atlassian',   'JIRA-STD-XXXX-XXXX-0007',   'subscription', 25, 13, '2025-10-15', '2026-10-15', 3500.00,  'active',  'Project tracking and bug management',                    1),
('Figma Professional',             'Figma',       'FIG-PRO-XXXX-XXXX-0008',    'subscription', 5,  3,  '2025-08-31', '2026-08-31', 1500.00,  'active',  'UI/UX design tool for marketing and dev',                1),
('Windows Server 2022 Standard',   'Microsoft',  'WS22-STD-XXXX-XXXX-0009',   'perpetual',    2,  2,  '2021-09-01', NULL,          12000.00, 'active',  'Licensed for 2 physical servers in server room',         1),
('MySQL Enterprise Edition',       'Oracle',      'MYSQL-ENT-XXXX-XXXX-0010',  'perpetual',    1,  1,  '2021-09-01', NULL,          8500.00,  'active',  'Production database server license',                     1),
('Norton Business Security',       'Norton',      'NBS-BUS-XXXX-XXXX-0011',    'subscription', 20, 13, '2024-03-15', '2026-03-15', 2100.00,  'expired', 'Expired — evaluate replacement with CrowdStrike',        1),
('Zoom Webinar (Add-on)',           'Zoom',        'ZM-WEB-XXXX-XXXX-0012',     'subscription', 1,  0,  '2024-05-01', '2026-05-01', 650.00,   'expired', 'Webinar capacity expired — renew for next company event',1);
