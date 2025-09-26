-- Agoraspace Community Marketplace Database Schema
-- This extends the existing O9Cart database with community marketplace functionality
-- and OpenCog AtomSpace integration for cognitive AI capabilities

-- Drop existing tables if they exist (for development)
DROP TABLE IF EXISTS `oc_agoraspace_market_signals`;
DROP TABLE IF EXISTS `oc_agoraspace_collaboration_members`;
DROP TABLE IF EXISTS `oc_agoraspace_collaborations`;
DROP TABLE IF EXISTS `oc_agoraspace_member_skills`;
DROP TABLE IF EXISTS `oc_agoraspace_skills`;
DROP TABLE IF EXISTS `oc_agoraspace_reputation_links`;
DROP TABLE IF EXISTS `oc_agoraspace_knowledge_contributions`;
DROP TABLE IF EXISTS `oc_agoraspace_agi_agents`;
DROP TABLE IF EXISTS `oc_agoraspace_members`;

-- Community Members Table
-- Extends user functionality with cognitive and community-specific features
CREATE TABLE `oc_agoraspace_members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `reputation_score` decimal(5,4) DEFAULT 0.5000,
  `contribution_score` int(11) DEFAULT 0,
  `skill_vector` json DEFAULT NULL,
  `cognitive_profile` json DEFAULT NULL,
  `community_role` enum('member','moderator','curator','agi_agent','mentor') DEFAULT 'member',
  `learning_style` enum('visual','auditory','kinesthetic','reading_writing','multimodal') DEFAULT 'multimodal',
  `collaboration_preferences` json DEFAULT NULL,
  `privacy_settings` json DEFAULT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `join_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `total_collaborations` int(11) DEFAULT 0,
  `successful_collaborations` int(11) DEFAULT 0,
  `knowledge_contributions` int(11) DEFAULT 0,
  `mentorship_sessions` int(11) DEFAULT 0,
  PRIMARY KEY (`member_id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `reputation_score` (`reputation_score`),
  KEY `community_role` (`community_role`),
  KEY `status` (`status`),
  CONSTRAINT `fk_agoraspace_members_user` FOREIGN KEY (`user_id`) REFERENCES `oc_user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Skills and Capabilities Registry
-- Defines the skills ecosystem within the community marketplace
CREATE TABLE `oc_agoraspace_skills` (
  `skill_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `subcategory` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `demand_level` decimal(3,2) DEFAULT 0.50,
  `supply_level` decimal(3,2) DEFAULT 0.50,
  `avg_hourly_rate` decimal(10,2) DEFAULT NULL,
  `complexity_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `learning_resources` json DEFAULT NULL,
  `related_skills` json DEFAULT NULL,
  `market_trends` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`skill_id`),
  UNIQUE KEY `name` (`name`),
  KEY `category` (`category`),
  KEY `demand_level` (`demand_level`),
  KEY `supply_level` (`supply_level`),
  KEY `complexity_level` (`complexity_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Member Skills Association
-- Links members to their skills with proficiency levels and verification status
CREATE TABLE `oc_agoraspace_member_skills` (
  `member_skill_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `proficiency_level` decimal(3,2) DEFAULT 0.50,
  `confidence_score` decimal(3,2) DEFAULT 0.50,
  `years_experience` int(11) DEFAULT 1,
  `verification_status` enum('self_reported','peer_verified','expert_verified','ai_assessed') DEFAULT 'self_reported',
  `verification_score` decimal(3,2) DEFAULT NULL,
  `last_used` timestamp NULL DEFAULT NULL,
  `learning_progress` json DEFAULT NULL,
  `endorsements` int(11) DEFAULT 0,
  `projects_completed` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`member_skill_id`),
  UNIQUE KEY `member_skill_unique` (`member_id`,`skill_id`),
  KEY `proficiency_level` (`proficiency_level`),
  KEY `verification_status` (`verification_status`),
  CONSTRAINT `fk_member_skills_member` FOREIGN KEY (`member_id`) REFERENCES `oc_agoraspace_members` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_skills_skill` FOREIGN KEY (`skill_id`) REFERENCES `oc_agoraspace_skills` (`skill_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reputation and Trust Network
-- Tracks trust relationships and reputation scores between community members
CREATE TABLE `oc_agoraspace_reputation_links` (
  `reputation_id` int(11) NOT NULL AUTO_INCREMENT,
  `from_member_id` int(11) NOT NULL,
  `to_member_id` int(11) NOT NULL,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `trust_score` decimal(3,2) DEFAULT 0.50,
  `collaboration_quality` decimal(3,2) DEFAULT NULL,
  `communication_rating` decimal(3,2) DEFAULT NULL,
  `reliability_score` decimal(3,2) DEFAULT NULL,
  `skill_accuracy` decimal(3,2) DEFAULT NULL,
  `interaction_type` enum('collaboration','mentorship','knowledge_sharing','transaction','peer_review') NOT NULL,
  `interaction_context` json DEFAULT NULL,
  `feedback_text` text DEFAULT NULL,
  `is_mutual` boolean DEFAULT FALSE,
  `weight` decimal(3,2) DEFAULT 1.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`reputation_id`),
  UNIQUE KEY `reputation_link_unique` (`from_member_id`,`to_member_id`,`interaction_type`),
  KEY `trust_score` (`trust_score`),
  KEY `interaction_type` (`interaction_type`),
  CONSTRAINT `fk_reputation_from_member` FOREIGN KEY (`from_member_id`) REFERENCES `oc_agoraspace_members` (`member_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reputation_to_member` FOREIGN KEY (`to_member_id`) REFERENCES `oc_agoraspace_members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collaborative Projects and Initiatives
-- Manages community-driven collaborative projects
CREATE TABLE `oc_agoraspace_collaborations` (
  `collaboration_id` int(11) NOT NULL AUTO_INCREMENT,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `project_type` enum('development','research','creative','educational','business','community_service') NOT NULL,
  `initiator_member_id` int(11) NOT NULL,
  `required_skills` json NOT NULL,
  `team_size_min` int(11) DEFAULT 2,
  `team_size_max` int(11) DEFAULT 10,
  `current_team_size` int(11) DEFAULT 1,
  `status` enum('proposed','recruiting','active','completed','paused','cancelled') DEFAULT 'proposed',
  `complexity_level` enum('simple','moderate','complex','expert') DEFAULT 'moderate',
  `estimated_duration` int(11) DEFAULT NULL, -- in days
  `actual_duration` int(11) DEFAULT NULL,
  `budget_range` varchar(50) DEFAULT NULL,
  `success_metrics` json DEFAULT NULL,
  `collaboration_model` enum('democratic','hierarchical','agile','open_source','ai_orchestrated') DEFAULT 'democratic',
  `agi_orchestrated` boolean DEFAULT FALSE,
  `orchestration_agent` varchar(255) DEFAULT NULL,
  `knowledge_artifacts` json DEFAULT NULL,
  `outcomes` json DEFAULT NULL,
  `lessons_learned` text DEFAULT NULL,
  `success_rating` decimal(3,2) DEFAULT NULL,
  `visibility` enum('public','private','invite_only') DEFAULT 'public',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`collaboration_id`),
  KEY `status` (`status`),
  KEY `project_type` (`project_type`),
  KEY `agi_orchestrated` (`agi_orchestrated`),
  KEY `success_rating` (`success_rating`),
  CONSTRAINT `fk_collaboration_initiator` FOREIGN KEY (`initiator_member_id`) REFERENCES `oc_agoraspace_members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Collaboration Team Members
-- Tracks participants in collaborative projects
CREATE TABLE `oc_agoraspace_collaboration_members` (
  `collaboration_member_id` int(11) NOT NULL AUTO_INCREMENT,
  `collaboration_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'contributor',
  `contribution_type` json DEFAULT NULL,
  `skills_contributed` json DEFAULT NULL,
  `commitment_level` enum('low','medium','high','full_time') DEFAULT 'medium',
  `status` enum('invited','accepted','active','completed','left','removed') DEFAULT 'invited',
  `contribution_score` decimal(3,2) DEFAULT 0.00,
  `peer_rating` decimal(3,2) DEFAULT NULL,
  `hours_contributed` decimal(5,2) DEFAULT 0.00,
  `knowledge_gained` json DEFAULT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `left_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`collaboration_member_id`),
  UNIQUE KEY `collaboration_member_unique` (`collaboration_id`,`member_id`),
  KEY `status` (`status`),
  KEY `contribution_score` (`contribution_score`),
  CONSTRAINT `fk_collab_member_collaboration` FOREIGN KEY (`collaboration_id`) REFERENCES `oc_agoraspace_collaborations` (`collaboration_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_collab_member_member` FOREIGN KEY (`member_id`) REFERENCES `oc_agoraspace_members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Knowledge Contributions and Sharing
-- Tracks knowledge sharing activities and contributions
CREATE TABLE `oc_agoraspace_knowledge_contributions` (
  `contribution_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` int(11) NOT NULL,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content_type` enum('article','tutorial','code','design','research','insight','question','answer') NOT NULL,
  `content` longtext NOT NULL,
  `tags` json DEFAULT NULL,
  `skill_categories` json DEFAULT NULL,
  `difficulty_level` enum('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
  `quality_score` decimal(3,2) DEFAULT NULL,
  `community_rating` decimal(3,2) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `like_count` int(11) DEFAULT 0,
  `comment_count` int(11) DEFAULT 0,
  `fork_count` int(11) DEFAULT 0,
  `implementation_count` int(11) DEFAULT 0,
  `is_verified` boolean DEFAULT FALSE,
  `verification_score` decimal(3,2) DEFAULT NULL,
  `license_type` varchar(50) DEFAULT 'CC-BY-SA',
  `visibility` enum('public','community','private') DEFAULT 'public',
  `ai_enhanced` boolean DEFAULT FALSE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`contribution_id`),
  KEY `content_type` (`content_type`),
  KEY `quality_score` (`quality_score`),
  KEY `community_rating` (`community_rating`),
  KEY `difficulty_level` (`difficulty_level`),
  CONSTRAINT `fk_knowledge_contributor` FOREIGN KEY (`member_id`) REFERENCES `oc_agoraspace_members` (`member_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- AGI Agents and Autonomous Services
-- Manages autonomous AI agents participating in the marketplace
CREATE TABLE `oc_agoraspace_agi_agents` (
  `agent_id` int(11) NOT NULL AUTO_INCREMENT,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `agent_type` enum('autonomous','ambient','service','facilitator','moderator','curator') NOT NULL,
  `specialization` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `capabilities` json DEFAULT NULL,
  `autonomy_level` enum('low','medium','high','full') DEFAULT 'medium',
  `learning_enabled` boolean DEFAULT TRUE,
  `interaction_protocols` json DEFAULT NULL,
  `performance_metrics` json DEFAULT NULL,
  `status` enum('active','inactive','learning','maintenance','error') DEFAULT 'active',
  `total_interactions` int(11) DEFAULT 0,
  `successful_interactions` int(11) DEFAULT 0,
  `community_rating` decimal(3,2) DEFAULT NULL,
  `trust_score` decimal(3,2) DEFAULT 0.50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`agent_id`),
  UNIQUE KEY `name` (`name`),
  KEY `agent_type` (`agent_type`),
  KEY `specialization` (`specialization`),
  KEY `status` (`status`),
  KEY `trust_score` (`trust_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Market Signals and Intelligence
-- Captures market dynamics, trends, and opportunities detected by AI
CREATE TABLE `oc_agoraspace_market_signals` (
  `signal_id` int(11) NOT NULL AUTO_INCREMENT,
  `atomspace_id` varchar(255) DEFAULT NULL,
  `signal_type` enum('demand_spike','supply_shortage','price_anomaly','trend_emergence','opportunity','skill_gap','collaboration_potential') NOT NULL,
  `entity_type` enum('skill','product','service','collaboration','member','knowledge','community') NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `entity_name` varchar(200) DEFAULT NULL,
  `signal_strength` decimal(3,2) NOT NULL,
  `confidence_level` decimal(3,2) NOT NULL,
  `predicted_duration` int(11) DEFAULT NULL, -- in hours
  `actual_duration` int(11) DEFAULT NULL,
  `impact_assessment` json DEFAULT NULL,
  `recommended_actions` json DEFAULT NULL,
  `agi_generated` boolean DEFAULT TRUE,
  `generating_agent` varchar(255) DEFAULT NULL,
  `validation_score` decimal(3,2) DEFAULT NULL,
  `community_feedback` json DEFAULT NULL,
  `market_context` json DEFAULT NULL,
  `related_signals` json DEFAULT NULL,
  `status` enum('active','expired','validated','invalidated','monitoring') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`signal_id`),
  KEY `signal_type` (`signal_type`),
  KEY `entity_type` (`entity_type`),
  KEY `signal_strength` (`signal_strength`),
  KEY `confidence_level` (`confidence_level`),
  KEY `status` (`status`),
  KEY `agi_generated` (`agi_generated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for development and testing

-- Sample skills
INSERT INTO `oc_agoraspace_skills` (`name`, `category`, `subcategory`, `description`, `demand_level`, `supply_level`, `complexity_level`) VALUES
('PHP Development', 'Programming', 'Backend', 'Server-side web development using PHP', 0.75, 0.60, 'intermediate'),
('JavaScript', 'Programming', 'Frontend', 'Client-side web development and interactivity', 0.85, 0.70, 'intermediate'),
('UX Design', 'Design', 'User Experience', 'User experience and interface design', 0.80, 0.45, 'intermediate'),
('Machine Learning', 'AI/ML', 'Data Science', 'Artificial intelligence and machine learning', 0.90, 0.30, 'advanced'),
('Project Management', 'Business', 'Leadership', 'Managing projects and teams effectively', 0.70, 0.55, 'intermediate'),
('Content Writing', 'Creative', 'Communication', 'Creating engaging written content', 0.65, 0.75, 'beginner'),
('OpenCog Development', 'AI/ML', 'Cognitive AI', 'Developing with OpenCog cognitive architecture', 0.95, 0.15, 'expert'),
('Community Moderation', 'Social', 'Governance', 'Managing online communities effectively', 0.60, 0.40, 'intermediate');

-- Sample AGI agents
INSERT INTO `oc_agoraspace_agi_agents` (`name`, `agent_type`, `specialization`, `description`, `capabilities`, `autonomy_level`, `status`) VALUES
('MarketTrendAnalyzer', 'ambient', 'market_analysis', 'Continuously analyzes marketplace trends and patterns', '["pattern_recognition", "trend_analysis", "market_prediction"]', 'high', 'active'),
('CollaborationMatchmaker', 'service', 'team_formation', 'Matches complementary skills and personalities for collaborations', '["skill_matching", "personality_analysis", "team_optimization"]', 'medium', 'active'),
('SkillGapIdentifier', 'autonomous', 'skill_analysis', 'Identifies skills shortages and surpluses in the community', '["demand_analysis", "supply_tracking", "gap_identification"]', 'high', 'active'),
('CommunityModerator', 'moderator', 'content_moderation', 'Provides AI-assisted community moderation and conflict resolution', '["content_analysis", "sentiment_detection", "conflict_resolution"]', 'medium', 'active'),
('KnowledgeEvolutionTracker', 'curator', 'knowledge_management', 'Tracks how knowledge evolves and spreads in the community', '["knowledge_mapping", "evolution_tracking", "wisdom_distillation"]', 'high', 'active');

-- Sample market signals
INSERT INTO `oc_agoraspace_market_signals` (`signal_type`, `entity_type`, `entity_name`, `signal_strength`, `confidence_level`, `predicted_duration`, `agi_generated`, `generating_agent`, `status`) VALUES
('demand_spike', 'skill', 'OpenCog Development', 0.85, 0.78, 168, TRUE, 'MarketTrendAnalyzer', 'active'),
('skill_gap', 'skill', 'UX Design', 0.70, 0.82, 720, TRUE, 'SkillGapIdentifier', 'active'),
('collaboration_potential', 'community', 'AI Research Group', 0.75, 0.65, 336, TRUE, 'CollaborationMatchmaker', 'active'),
('trend_emergence', 'skill', 'Cognitive AI Integration', 0.90, 0.85, 2160, TRUE, 'MarketTrendAnalyzer', 'active');

-- Create indexes for better performance
CREATE INDEX idx_member_reputation ON oc_agoraspace_members(reputation_score DESC);
CREATE INDEX idx_member_activity ON oc_agoraspace_members(last_activity DESC);
CREATE INDEX idx_skill_demand ON oc_agoraspace_skills(demand_level DESC, supply_level ASC);
CREATE INDEX idx_collaboration_status ON oc_agoraspace_collaborations(status, created_at DESC);
CREATE INDEX idx_market_signals_active ON oc_agoraspace_market_signals(status, signal_strength DESC, created_at DESC);
CREATE INDEX idx_knowledge_quality ON oc_agoraspace_knowledge_contributions(quality_score DESC, community_rating DESC);
CREATE INDEX idx_reputation_trust ON oc_agoraspace_reputation_links(trust_score DESC, created_at DESC);

-- Create views for common queries
CREATE VIEW v_agoraspace_member_summary AS
SELECT 
    m.member_id,
    m.username,
    m.reputation_score,
    m.contribution_score,
    m.community_role,
    m.total_collaborations,
    m.successful_collaborations,
    CASE 
        WHEN m.total_collaborations > 0 
        THEN (m.successful_collaborations / m.total_collaborations) 
        ELSE 0 
    END as success_rate,
    COUNT(ms.skill_id) as skill_count,
    AVG(ms.proficiency_level) as avg_skill_level,
    m.last_activity
FROM oc_agoraspace_members m
LEFT JOIN oc_agoraspace_member_skills ms ON m.member_id = ms.member_id
GROUP BY m.member_id;

CREATE VIEW v_agoraspace_skill_marketplace AS
SELECT 
    s.skill_id,
    s.name,
    s.category,
    s.demand_level,
    s.supply_level,
    (s.demand_level - s.supply_level) as market_tension,
    COUNT(ms.member_id) as available_practitioners,
    AVG(ms.proficiency_level) as avg_proficiency,
    s.avg_hourly_rate
FROM oc_agoraspace_skills s
LEFT JOIN oc_agoraspace_member_skills ms ON s.skill_id = ms.skill_id
GROUP BY s.skill_id;

CREATE VIEW v_agoraspace_active_collaborations AS
SELECT 
    c.collaboration_id,
    c.title,
    c.project_type,
    c.status,
    c.current_team_size,
    c.team_size_max,
    c.agi_orchestrated,
    c.success_rating,
    m.username as initiator_username,
    DATEDIFF(CURRENT_DATE, c.created_at) as days_since_creation
FROM oc_agoraspace_collaborations c
JOIN oc_agoraspace_members m ON c.initiator_member_id = m.member_id
WHERE c.status IN ('proposed', 'recruiting', 'active');