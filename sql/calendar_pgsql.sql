--
-- Base de données: "everlong"
--

-- --------------------------------------------------------

--
-- Structure de la table "cal_calendars"
--

CREATE TABLE "cal_calendars" (
  "id" serial NOT NULL,
  "id_owner" integer NOT NULL default '1',
  "name" varchar(50) NOT NULL default '',
  "rights_users" smallint NOT NULL default '0',
  "rights_subscribed" smallint NOT NULL default '0',
  "rights_guests" smallint NOT NULL default '0',
  "rss_last" varchar(50) NOT NULL default '',
  "rss_next" varchar(50) NOT NULL default '',
  "rss_next_user" varchar(50) NOT NULL default '',
  "new_event_label" varchar(100) NOT NULL default 'Nouvel événement',
  "active" boolean NOT NULL default '0',
  PRIMARY KEY  ("id")
);

-- --------------------------------------------------------

--
-- Structure de la table "cal_calendars_users"
--

CREATE TABLE "cal_calendars_users" (
  "id_cal" integer NOT NULL default '0',
  "id_user" integer NOT NULL default '0',
  PRIMARY KEY  ("id_cal","id_user")
);
CREATE INDEX "calendars_users_id_user_idx" ON cal_calendars_users (id_user);

-- --------------------------------------------------------

--
-- Structure de la table "cal_events"
--

CREATE TABLE "cal_events" (
  "id" serial NOT NULL,
  "id_cal" integer NOT NULL default '0',
  "event" varchar(255) NOT NULL default '',
  "jour" date NOT NULL,
  "horaire" time default NULL,
  "id_submitter" integer default NULL,
  "submit_timestamp" timestamp NOT NULL default NOW(),
  PRIMARY KEY  ("id")
);

CREATE INDEX "events_id_cal_idx" ON cal_events (id_cal);
CREATE INDEX "events_jour_idx" ON cal_events (jour);

-- --------------------------------------------------------

--
-- Structure de la table "cal_events_users"
--

CREATE TABLE "cal_events_users" (
  "id_event" integer NOT NULL default '0',
  "id_user" integer NOT NULL default '0',
  "sure" boolean NOT NULL default '1',
  PRIMARY KEY  ("id_event","id_user")
);
CREATE INDEX "events_users_id_user_idx" ON cal_events_users (id_user);

-- --------------------------------------------------------

--
-- Structure de la table "cal_log"
--

CREATE TABLE "cal_log" (
  "id" serial NOT NULL,
  "logtime" timestamp NOT NULL default NOW(),
  "ident" varchar(16) NOT NULL default '',
  "priority" smallint NOT NULL default '0',
  "message" varchar(200) default NULL,
  PRIMARY KEY  ("id")
);

-- --------------------------------------------------------

--
-- Structure de la table "cal_users"
--

CREATE TABLE "cal_users" (
  "id" serial NOT NULL,
  "username" varchar(20) NOT NULL default '',
  "passwd" bytea NOT NULL default '',
  "email" varchar(250) NOT NULL default '',
  "confirmcookie" bytea default NULL,
  PRIMARY KEY  ("id"),
  UNIQUE ("username")
);

CREATE INDEX "users_email_idx" ON cal_users (email);

