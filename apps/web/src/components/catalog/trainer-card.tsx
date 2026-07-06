import type { Trainer } from "@/lib/catalog/api";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";

function initials(name: string) {
  return name.split(" ").map((p) => p[0]).slice(0, 2).join("").toUpperCase();
}

export function TrainerCard({ trainer }: { trainer: Trainer }) {
  return (
    <Card className="card-hover hover:border-primary/30 hover:shadow-lg">
      <CardContent className="flex items-center gap-4 p-5">
        <Avatar className="size-14">
          <AvatarFallback className="text-base">{initials(trainer.name)}</AvatarFallback>
        </Avatar>
        <div className="min-w-0">
          <p className="truncate font-serif font-semibold">{trainer.name}</p>
          {trainer.headline ? <p className="line-clamp-2 text-sm text-muted-foreground">{trainer.headline}</p> : null}
        </div>
      </CardContent>
    </Card>
  );
}
