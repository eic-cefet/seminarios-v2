import type { Page } from "playwright";

export interface GuideStep {
  title: string;
  description: string;
  screenshotName: string;
  action: (page: Page) => Promise<void>;
}

export interface Guide {
  id: string;
  title: string;
  description: string;
  category: "admin" | "usuario";
  steps: GuideStep[];
}

export type GuideFactory = () => Guide;
