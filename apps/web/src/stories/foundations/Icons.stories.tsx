import type { Meta, StoryObj } from "@storybook/react";
import {
  BookOpen, GraduationCap, Award, Calendar, Users, BarChart3, Settings, Bell,
  Search, Home, User, Heart, Star, Play, Download, Check, X, ChevronRight,
  ArrowRight, Mail, Phone, MapPin, Clock, CreditCard, ShoppingCart, FileText,
  Video, Globe, Menu, Trash2, Pencil, Plus, LogOut,
} from "lucide-react";
import { Icon, ICON_SIZES, type IconSize } from "@/components/ui/icon";

/**
 * Foundations · Icons
 * --------------------
 * The Design System uses **lucide-react** as its single icon family, wrapped by the
 * `<Icon>` primitive which standardises size tokens (xs 14 · sm 16 · md 20 · lg 24 ·
 * xl 32), stroke width (1.75) and a11y semantics (`label` → role="img"; otherwise
 * aria-hidden). Prefer `<Icon icon={SomeGlyph} />` over raw lucide imports.
 */

const SIZES = Object.keys(ICON_SIZES) as IconSize[];

const SAMPLE = [
  { icon: BookOpen, name: "BookOpen" },
  { icon: GraduationCap, name: "GraduationCap" },
  { icon: Award, name: "Award" },
  { icon: Calendar, name: "Calendar" },
  { icon: Users, name: "Users" },
  { icon: BarChart3, name: "BarChart3" },
  { icon: Settings, name: "Settings" },
  { icon: Bell, name: "Bell" },
  { icon: Search, name: "Search" },
  { icon: Home, name: "Home" },
  { icon: User, name: "User" },
  { icon: Heart, name: "Heart" },
  { icon: Star, name: "Star" },
  { icon: Play, name: "Play" },
  { icon: Download, name: "Download" },
  { icon: Check, name: "Check" },
  { icon: X, name: "X" },
  { icon: ChevronRight, name: "ChevronRight" },
  { icon: ArrowRight, name: "ArrowRight" },
  { icon: Mail, name: "Mail" },
  { icon: Phone, name: "Phone" },
  { icon: MapPin, name: "MapPin" },
  { icon: Clock, name: "Clock" },
  { icon: CreditCard, name: "CreditCard" },
  { icon: ShoppingCart, name: "ShoppingCart" },
  { icon: FileText, name: "FileText" },
  { icon: Video, name: "Video" },
  { icon: Globe, name: "Globe" },
  { icon: Menu, name: "Menu" },
  { icon: Trash2, name: "Trash2" },
  { icon: Pencil, name: "Pencil" },
  { icon: Plus, name: "Plus" },
  { icon: LogOut, name: "LogOut" },
];

const meta: Meta = {
  title: "Foundations/Icons",
  parameters: { layout: "padded", a11y: { test: "off" } },
  tags: ["autodocs"],
};
export default meta;
type Story = StoryObj;

export const Sizes: Story = {
  render: () => (
    <div className="flex items-end gap-8">
      {SIZES.map((size) => (
        <div key={size} className="text-center text-caption">
          <Icon icon={GraduationCap} size={size} className="mx-auto text-primary" />
          <div className="mt-2 font-mono text-muted-foreground">
            {size} · {ICON_SIZES[size]}px
          </div>
        </div>
      ))}
    </div>
  ),
};

export const Library: Story = {
  render: () => (
    <div className="grid grid-cols-3 gap-4 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8">
      {SAMPLE.map(({ icon, name }) => (
        <div
          key={name}
          className="flex flex-col items-center gap-2 rounded-lg border border-border bg-card p-3 text-center"
        >
          <Icon icon={icon} size="lg" label={name} className="text-foreground" />
          <span className="text-[11px] text-muted-foreground">{name}</span>
        </div>
      ))}
    </div>
  ),
};

export const OnColorSurfaces: Story = {
  render: () => (
    <div className="flex flex-wrap gap-4">
      <div className="flex items-center gap-2 rounded-lg bg-primary px-4 py-3 text-primary-foreground">
        <Icon icon={Award} size="md" /> Primary
      </div>
      <div className="flex items-center gap-2 rounded-lg bg-success px-4 py-3 text-success-foreground">
        <Icon icon={Check} size="md" /> Success
      </div>
      <div className="flex items-center gap-2 rounded-lg bg-destructive px-4 py-3 text-destructive-foreground">
        <Icon icon={X} size="md" /> Destructive
      </div>
      <div className="flex items-center gap-2 rounded-lg bg-info px-4 py-3 text-info-foreground">
        <Icon icon={Bell} size="md" /> Info
      </div>
    </div>
  ),
};
