import Link from "next/link";
import { FolderTree } from "lucide-react";
import type { Category } from "@/lib/catalog/api";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

export function CategoryCard({ category }: { category: Category }) {
  const children = category.children ?? [];
  return (
    <Card className="card-hover h-full hover:border-primary/30 hover:elevation-3">
      <CardContent className="space-y-3 p-5">
        <div className="flex items-center gap-3">
          <div className="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
            <FolderTree className="size-5" aria-hidden />
          </div>
          <Link href={`/courses?category=${category.id}`} className="font-serif font-semibold hover:underline">
            {category.name}
          </Link>
        </div>
        {category.description ? <p className="text-sm text-muted-foreground">{category.description}</p> : null}
        {children.length > 0 ? (
          <div className="flex flex-wrap gap-1.5">
            {children.map((c) => (
              <Link key={c.id} href={`/courses?category=${c.id}`}>
                <Badge variant="secondary" className="hover:bg-secondary/70">{c.name}</Badge>
              </Link>
            ))}
          </div>
        ) : null}
      </CardContent>
    </Card>
  );
}
