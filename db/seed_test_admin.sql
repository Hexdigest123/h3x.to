-- Default credentials:
-- Name: hexdigest_admin
-- Password: Admin!234

INSERT INTO users (name, password, role, is_active)
VALUES ('hexdigest_admin', '$2y$10$aflV9avwoDg6AkivM3aZ8uatNXTiXYniG5asZonC9kajDsYyEtc2.', 'Admin', TRUE)
ON CONFLICT (name) DO NOTHING;
