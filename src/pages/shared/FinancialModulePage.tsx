import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { ChartContainer, ChartTooltip, ChartTooltipContent } from "@/components/ui/chart";
import { Bar, BarChart, CartesianGrid, Line, LineChart, Pie, PieChart, XAxis, YAxis } from "recharts";

type FinancialSummary = {
  total_revenue?: number;
  total_due?: number;
  paid_count?: number;
  unpaid_count?: number;
};

type FinancialTrend = {
  month?: string;
  revenue?: number;
  due?: number;
  clinic?: number;
  lab?: number;
  radiology?: number;
};

type FinancialBreakdown = {
  name?: string;
  value?: number;
};

type FinancialModulePageProps = {
  title: string;
  description: string;
  queryKeyBase: string[];
  queryFn: (months: number) => Promise<unknown>;
};

export default function FinancialModulePage({ title, description, queryKeyBase, queryFn }: FinancialModulePageProps) {
  const [months, setMonths] = useState(6);
  const { data, isLoading, error } = useQuery({
    queryKey: [...queryKeyBase, months],
    queryFn: () => queryFn(months),
  });

  const root = (data as { data?: unknown })?.data ?? data;
  const summary = ((root as { summary?: FinancialSummary })?.summary ?? {}) as FinancialSummary;
  const trend = (((root as { trend?: FinancialTrend[] })?.trend ?? []) as FinancialTrend[]).map((item) => ({
    month: item.month ?? "",
    revenue: Number(item.revenue ?? 0),
    due: Number(item.due ?? 0),
    clinic: Number(item.clinic ?? 0),
    lab: Number(item.lab ?? 0),
    radiology: Number(item.radiology ?? 0),
  }));
  const breakdown = (((root as { breakdown?: FinancialBreakdown[] })?.breakdown ?? []) as FinancialBreakdown[]).map((item) => ({
    name: item.name ?? "",
    value: Number(item.value ?? 0),
  }));

  const money = (value: number | undefined) =>
    new Intl.NumberFormat("en-US", { style: "currency", currency: "EGP", maximumFractionDigits: 0 }).format(Number(value ?? 0));

  const showAdminSourceBars = useMemo(
    () => trend.some((point) => (point.clinic ?? 0) > 0 || (point.lab ?? 0) > 0 || (point.radiology ?? 0) > 0),
    [trend],
  );

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h2 className="text-2xl font-bold">{title}</h2>
          <p className="text-sm text-muted-foreground mt-1">{description}</p>
        </div>
        <div className="w-44">
          <Select value={String(months)} onValueChange={(value) => setMonths(Number(value))}>
            <SelectTrigger>
              <SelectValue placeholder="Period" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="3">Last 3 months</SelectItem>
              <SelectItem value="6">Last 6 months</SelectItem>
              <SelectItem value="12">Last 12 months</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      {isLoading && <Card><CardContent className="p-6 text-muted-foreground">Loading financial data...</CardContent></Card>}
      {error && <Card><CardContent className="p-6 text-destructive">{error instanceof Error ? error.message : "Failed to load financial data"}</CardContent></Card>}

      {!isLoading && !error && (
        <>
          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card><CardHeader><CardTitle className="text-sm">Total Revenue</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{money(summary.total_revenue)}</CardContent></Card>
            <Card><CardHeader><CardTitle className="text-sm">Total Due</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{money(summary.total_due)}</CardContent></Card>
            <Card><CardHeader><CardTitle className="text-sm">Paid Records</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{summary.paid_count ?? 0}</CardContent></Card>
            <Card><CardHeader><CardTitle className="text-sm">Unpaid Records</CardTitle></CardHeader><CardContent className="text-2xl font-semibold">{summary.unpaid_count ?? 0}</CardContent></Card>
          </div>

          <div className="grid lg:grid-cols-3 gap-4">
            <Card className="lg:col-span-2">
              <CardHeader><CardTitle className="text-base">Revenue Trend</CardTitle></CardHeader>
              <CardContent>
                <ChartContainer
                  config={{
                    revenue: { label: "Revenue", color: "hsl(var(--chart-1))" },
                    due: { label: "Due", color: "hsl(var(--chart-2))" },
                    clinic: { label: "Clinic", color: "hsl(var(--chart-3))" },
                    lab: { label: "Lab", color: "hsl(var(--chart-4))" },
                    radiology: { label: "Radiology", color: "hsl(var(--chart-5))" },
                  }}
                  className="h-72 w-full"
                >
                  {showAdminSourceBars ? (
                    <BarChart data={trend}>
                      <CartesianGrid vertical={false} />
                      <XAxis dataKey="month" tickLine={false} axisLine={false} />
                      <YAxis tickLine={false} axisLine={false} />
                      <ChartTooltip content={<ChartTooltipContent />} />
                      <Bar dataKey="clinic" stackId="a" fill="var(--color-clinic)" radius={[4, 4, 0, 0]} />
                      <Bar dataKey="lab" stackId="a" fill="var(--color-lab)" />
                      <Bar dataKey="radiology" stackId="a" fill="var(--color-radiology)" />
                    </BarChart>
                  ) : (
                    <LineChart data={trend}>
                      <CartesianGrid vertical={false} />
                      <XAxis dataKey="month" tickLine={false} axisLine={false} />
                      <YAxis tickLine={false} axisLine={false} />
                      <ChartTooltip content={<ChartTooltipContent />} />
                      <Line type="monotone" dataKey="revenue" stroke="var(--color-revenue)" strokeWidth={2} dot={false} />
                      <Line type="monotone" dataKey="due" stroke="var(--color-due)" strokeWidth={2} dot={false} />
                    </LineChart>
                  )}
                </ChartContainer>
              </CardContent>
            </Card>

            <Card>
              <CardHeader><CardTitle className="text-base">Breakdown</CardTitle></CardHeader>
              <CardContent>
                <ChartContainer
                  config={{
                    paid: { label: "Paid", color: "hsl(var(--chart-1))" },
                    due: { label: "Due", color: "hsl(var(--chart-2))" },
                    clinic: { label: "Clinic", color: "hsl(var(--chart-3))" },
                    laboratory: { label: "Laboratory", color: "hsl(var(--chart-4))" },
                    radiology: { label: "Radiology", color: "hsl(var(--chart-5))" },
                  }}
                  className="h-72 w-full"
                >
                  <PieChart>
                    <ChartTooltip content={<ChartTooltipContent />} />
                    <Pie data={breakdown} dataKey="value" nameKey="name" cx="50%" cy="50%" outerRadius={90} fill="hsl(var(--chart-1))" />
                  </PieChart>
                </ChartContainer>
              </CardContent>
            </Card>
          </div>
        </>
      )}
    </div>
  );
}
