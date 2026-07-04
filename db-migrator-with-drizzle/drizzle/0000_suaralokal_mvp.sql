CREATE TABLE "cache_locks" (
	"key" varchar(255) PRIMARY KEY NOT NULL,
	"owner" varchar(255) NOT NULL,
	"expiration" bigint NOT NULL
);
--> statement-breakpoint
CREATE TABLE "cache" (
	"key" varchar(255) PRIMARY KEY NOT NULL,
	"value" text NOT NULL,
	"expiration" bigint NOT NULL
);
--> statement-breakpoint
CREATE TABLE "conversations" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"pengguna_id" bigint NOT NULL,
	"umkm_id" bigint NOT NULL,
	"created_at" timestamp,
	"updated_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "failed_jobs" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"uuid" varchar(255) NOT NULL,
	"connection" varchar(255) NOT NULL,
	"queue" varchar(255) NOT NULL,
	"payload" text NOT NULL,
	"exception" text NOT NULL,
	"failed_at" timestamp DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "job_batches" (
	"id" varchar(255) PRIMARY KEY NOT NULL,
	"name" varchar(255) NOT NULL,
	"total_jobs" integer NOT NULL,
	"pending_jobs" integer NOT NULL,
	"failed_jobs" integer NOT NULL,
	"failed_job_ids" text NOT NULL,
	"options" text,
	"cancelled_at" integer,
	"created_at" integer NOT NULL,
	"finished_at" integer
);
--> statement-breakpoint
CREATE TABLE "jobs" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"queue" varchar(255) NOT NULL,
	"payload" text NOT NULL,
	"attempts" smallint NOT NULL,
	"reserved_at" integer,
	"available_at" integer NOT NULL,
	"created_at" integer NOT NULL
);
--> statement-breakpoint
CREATE TABLE "messages" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"conversation_id" bigint NOT NULL,
	"sender_id" bigint NOT NULL,
	"content" text NOT NULL,
	"created_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "migrations" (
	"id" serial PRIMARY KEY NOT NULL,
	"migration" varchar(255) NOT NULL,
	"batch" integer NOT NULL
);
--> statement-breakpoint
CREATE TABLE "order_items" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"order_id" bigint NOT NULL,
	"item_name" varchar(255) NOT NULL,
	"quantity" integer DEFAULT 1 NOT NULL,
	"price" numeric(12, 2) DEFAULT '0' NOT NULL,
	"created_at" timestamp,
	"updated_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "orders" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"pengguna_id" bigint NOT NULL,
	"umkm_id" bigint NOT NULL,
	"driver_id" bigint,
	"conversation_id" bigint,
	"total_items_price" numeric(12, 2) DEFAULT '0' NOT NULL,
	"shipping_fee" numeric(12, 2) DEFAULT '0' NOT NULL,
	"payment_method" varchar(20) NOT NULL,
	"order_status" varchar(30) DEFAULT 'tunggu_konfirm' NOT NULL,
	"proof_of_delivery_url" varchar(500),
	"created_at" timestamp,
	"updated_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "password_reset_tokens" (
	"email" varchar(255) PRIMARY KEY NOT NULL,
	"token" varchar(255) NOT NULL,
	"created_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "sessions" (
	"id" varchar(255) PRIMARY KEY NOT NULL,
	"user_id" bigint,
	"ip_address" varchar(45),
	"user_agent" text,
	"payload" text NOT NULL,
	"last_activity" integer NOT NULL
);
--> statement-breakpoint
CREATE TABLE "sidebar_menu_accesses" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"sidebar_menu_id" bigint NOT NULL,
	"access_type" smallint NOT NULL,
	"created_by" bigint,
	"created_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "sidebar_menu_groups" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"key" varchar(50) NOT NULL,
	"label" varchar(100) NOT NULL,
	"color" varchar(50) DEFAULT 'blue' NOT NULL,
	"sort_order" smallint DEFAULT 0 NOT NULL,
	"created_at" timestamp,
	"updated_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "sidebar_menus" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"parent_id" bigint,
	"label" varchar(255) NOT NULL,
	"route_name" varchar(255),
	"icon" varchar(255),
	"group" varchar(50) NOT NULL,
	"sort_order" smallint DEFAULT 0 NOT NULL,
	"is_active" smallint DEFAULT 1 NOT NULL,
	"created_by" bigint,
	"updated_by" bigint,
	"deleted_by" bigint,
	"created_at" timestamp,
	"updated_at" timestamp,
	"deleted_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "umkm_profiles" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"user_id" bigint NOT NULL,
	"store_name" varchar(255) NOT NULL,
	"description" text,
	"address" text,
	"phone" varchar(20),
	"category" varchar(50),
	"item_dimension" varchar(20),
	"is_open" boolean DEFAULT false NOT NULL,
	"latitude" numeric(10, 7),
	"longitude" numeric(10, 7),
	"created_at" timestamp,
	"updated_at" timestamp
);
--> statement-breakpoint
CREATE TABLE "users" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"name" varchar(255) NOT NULL,
	"email" varchar(255) NOT NULL,
	"email_verified_at" timestamp,
	"password" varchar(255) NOT NULL,
	"remember_token" varchar(100),
	"access_type" smallint,
	"role" varchar(50) DEFAULT 'pengguna' NOT NULL,
	"phone" varchar(20),
	"is_active" smallint DEFAULT 1 NOT NULL,
	"created_by" bigint,
	"created_at" timestamp,
	"updated_by" bigint,
	"updated_at" timestamp,
	"deleted_by" bigint,
	"deleted_at" timestamp
);
--> statement-breakpoint
ALTER TABLE "conversations" ADD CONSTRAINT "conversations_pengguna_id_users_id_fk" FOREIGN KEY ("pengguna_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "conversations" ADD CONSTRAINT "conversations_umkm_id_users_id_fk" FOREIGN KEY ("umkm_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "messages" ADD CONSTRAINT "messages_conversation_id_conversations_id_fk" FOREIGN KEY ("conversation_id") REFERENCES "public"."conversations"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "messages" ADD CONSTRAINT "messages_sender_id_users_id_fk" FOREIGN KEY ("sender_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "order_items" ADD CONSTRAINT "order_items_order_id_orders_id_fk" FOREIGN KEY ("order_id") REFERENCES "public"."orders"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "orders" ADD CONSTRAINT "orders_pengguna_id_users_id_fk" FOREIGN KEY ("pengguna_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "orders" ADD CONSTRAINT "orders_umkm_id_users_id_fk" FOREIGN KEY ("umkm_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "orders" ADD CONSTRAINT "orders_driver_id_users_id_fk" FOREIGN KEY ("driver_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "orders" ADD CONSTRAINT "orders_conversation_id_conversations_id_fk" FOREIGN KEY ("conversation_id") REFERENCES "public"."conversations"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "umkm_profiles" ADD CONSTRAINT "umkm_profiles_user_id_users_id_fk" FOREIGN KEY ("user_id") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "users" ADD CONSTRAINT "users_created_by_users_id_fk" FOREIGN KEY ("created_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "users" ADD CONSTRAINT "users_updated_by_users_id_fk" FOREIGN KEY ("updated_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
ALTER TABLE "users" ADD CONSTRAINT "users_deleted_by_users_id_fk" FOREIGN KEY ("deleted_by") REFERENCES "public"."users"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
CREATE INDEX "cache_locks_expiration_index" ON "cache_locks" USING btree ("expiration");--> statement-breakpoint
CREATE INDEX "cache_expiration_index" ON "cache" USING btree ("expiration");--> statement-breakpoint
CREATE UNIQUE INDEX "conversations_pengguna_umkm_unique" ON "conversations" USING btree ("pengguna_id","umkm_id");--> statement-breakpoint
CREATE INDEX "conversations_pengguna_id_index" ON "conversations" USING btree ("pengguna_id");--> statement-breakpoint
CREATE INDEX "conversations_umkm_id_index" ON "conversations" USING btree ("umkm_id");--> statement-breakpoint
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" ON "failed_jobs" USING btree ("uuid");--> statement-breakpoint
CREATE INDEX "failed_jobs_connection_queue_failed_at_index" ON "failed_jobs" USING btree ("connection","queue","failed_at");--> statement-breakpoint
CREATE INDEX "jobs_queue_index" ON "jobs" USING btree ("queue");--> statement-breakpoint
CREATE INDEX "messages_conversation_id_index" ON "messages" USING btree ("conversation_id");--> statement-breakpoint
CREATE INDEX "messages_sender_id_index" ON "messages" USING btree ("sender_id");--> statement-breakpoint
CREATE INDEX "order_items_order_id_index" ON "order_items" USING btree ("order_id");--> statement-breakpoint
CREATE INDEX "orders_pengguna_id_index" ON "orders" USING btree ("pengguna_id");--> statement-breakpoint
CREATE INDEX "orders_umkm_id_index" ON "orders" USING btree ("umkm_id");--> statement-breakpoint
CREATE INDEX "orders_driver_id_index" ON "orders" USING btree ("driver_id");--> statement-breakpoint
CREATE INDEX "orders_order_status_index" ON "orders" USING btree ("order_status");--> statement-breakpoint
CREATE INDEX "sessions_user_id_index" ON "sessions" USING btree ("user_id");--> statement-breakpoint
CREATE INDEX "sessions_last_activity_index" ON "sessions" USING btree ("last_activity");--> statement-breakpoint
CREATE UNIQUE INDEX "sidebar_menu_accesses_sidebar_menu_id_access_type_unique" ON "sidebar_menu_accesses" USING btree ("sidebar_menu_id","access_type");--> statement-breakpoint
CREATE INDEX "sidebar_menu_accesses_sidebar_menu_id_index" ON "sidebar_menu_accesses" USING btree ("sidebar_menu_id");--> statement-breakpoint
CREATE UNIQUE INDEX "sidebar_menu_groups_key_unique" ON "sidebar_menu_groups" USING btree ("key");--> statement-breakpoint
CREATE INDEX "sidebar_menus_parent_id_index" ON "sidebar_menus" USING btree ("parent_id");--> statement-breakpoint
CREATE INDEX "sidebar_menus_group_sort_order_index" ON "sidebar_menus" USING btree ("group","sort_order");--> statement-breakpoint
CREATE UNIQUE INDEX "umkm_profiles_user_id_unique" ON "umkm_profiles" USING btree ("user_id");--> statement-breakpoint
CREATE UNIQUE INDEX "users_email_unique" ON "users" USING btree ("email");