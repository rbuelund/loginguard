CREATE TABLE "#__loginguard_tfa" (
  "id" SERIAL NOT NULL,
  "user_id" BIGINT DEFAULT 0 NOT NULL,
  "title" VARCHAR(180) NOT NULL,
  "method" VARCHAR(100) NOT NULL,
  "default" SMALLINT DEFAULT 0 NOT NULL ,
  "options" TEXT null,
  "created_on" TIMESTAMP WITHOUT TIME ZONE DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "last_used" TIMESTAMP WITHOUT TIME ZONE DEFAULT '1970-01-01 00:00:00' NOT NULL,
  "ua" VARCHAR(190) NULL,
  "ip" VARCHAR(190) NULL,
  PRIMARY KEY ("id")
);

CREATE INDEX "#__loginguard_tfa_user_id" ON "#__loginguard_tfa" ("user_id");
