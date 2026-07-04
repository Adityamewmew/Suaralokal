CREATE TABLE "settlements" (
	"id" bigserial PRIMARY KEY NOT NULL,
	"order_id" bigint NOT NULL,
	"amount" numeric(12, 2) DEFAULT '0' NOT NULL,
	"status" varchar(30) DEFAULT 'menunggu_reimburse' NOT NULL,
	"settled_at" timestamp,
	"created_at" timestamp,
	"updated_at" timestamp
);
--> statement-breakpoint
ALTER TABLE "settlements" ADD CONSTRAINT "settlements_order_id_orders_id_fk" FOREIGN KEY ("order_id") REFERENCES "public"."orders"("id") ON DELETE no action ON UPDATE no action;--> statement-breakpoint
CREATE INDEX "settlements_order_id_index" ON "settlements" USING btree ("order_id");--> statement-breakpoint
CREATE INDEX "settlements_status_index" ON "settlements" USING btree ("status");