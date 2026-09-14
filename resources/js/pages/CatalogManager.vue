<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { Trash2, Settings, Plus, Image as ImageIcon } from 'lucide-vue-next'; 

import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    products: any[];
    categories: any[];
    subcategories: any[];
    product_sizes: any[];
    product_finish: any[];
    product_color: any[];
    product_material: any[];
    product_images: any[];
    
    // Pivot table mappings
    product_color_map: any[];
    product_size_map: any[];
    product_material_map: any[];
    product_finish_map: any[];
}>();

const isProductModalOpen = ref(false);
const isAttributeSettingsOpen = ref(false);
const isAttributeModalOpen = ref(false);
const currentAttributeType = ref('');
const currentAttributeTitle = ref('');

// Product Form
const productForm = useForm({
    id: null as number | null,
    name: '',
    dics: '',
    price: '',
    discount_price: '',
    sub_category_id: '',
    colors: [] as number[],
    sizes: [] as number[],
    materials: [] as number[],
    finishes: [] as number[],
    images: [] as File[], 
});

const attributeForm = useForm({
    name: '',
    abriviation: '', 
    category_id: '', 
});

const getCategoryName = (id: number) => {
    const cat = props.categories.find(c => c.id === id);
    return cat ? cat.name : 'Unknown';
};

const openAttributeModal = (type: string, title: string) => {
    attributeForm.reset();
    currentAttributeType.value = type;
    currentAttributeTitle.value = title;
    isAttributeModalOpen.value = true;
};

const openProductModal = (product: any = null) => {
    productForm.reset();
    if (product) {
        productForm.id = product.id;
        productForm.name = product.name;
        productForm.dics = product.dics;
        productForm.price = product.price;
        productForm.discount_price = product.discount_price;
        productForm.sub_category_id = product.sub_category_id;
        
        productForm.colors = props.product_color_map.filter(m => m.product_id === product.id).map(m => m.color_id);
        productForm.sizes = props.product_size_map.filter(m => m.product_id === product.id).map(m => m.size_id);
        productForm.materials = props.product_material_map.filter(m => m.product_id === product.id).map(m => m.material_id);
        productForm.finishes = props.product_finish_map.filter(m => m.product_id === product.id).map(m => m.finish_id);
    }
    isProductModalOpen.value = true;
};

const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        productForm.images = Array.from(target.files);
    }
};

const saveAttribute = () => {
    attributeForm.post(`/catalog/${currentAttributeType.value}`, {
        onSuccess: () => {
            isAttributeModalOpen.value = false;
            attributeForm.reset();
        }
    });
};

const saveProduct = () => {
    productForm.transform((data) => ({
        ...data,
        colors: data.colors.join(','),
        sizes: data.sizes.join(','),
        materials: data.materials.join(','),
        finishes: data.finishes.join(','),
    })).post(`/catalog/products`, {
        onSuccess: () => {
            isProductModalOpen.value = false;
            productForm.reset();
        }
    });
};

const deleteItem = (table: string, id: number) => {
    if (confirm('Kya aap waqai is record ko delete karna chahte hain?')) {
        router.delete(`/catalog/${table}/${id}`);
    }
};
</script>

<template>
    <Head title="Catalog Manager" />

    <div class="flex flex-col gap-6 p-6 max-w-8xl">
        <div class="flex justify-between items-center">
            <h1 class="text-3xl font-bold tracking-tight">Catalog Management</h1>
            <Button variant="outline" class="gap-2" @click="isAttributeSettingsOpen = true">
                <Settings class="w-4 h-4" /> Attributes Settings
            </Button>
        </div>

        <Tabs defaultValue="products" class="w-full">
            <TabsList class="grid w-full grid-cols-2 mb-6 max-w-md">
                <TabsTrigger value="products">Products</TabsTrigger>
                <TabsTrigger value="categories">Categories & Sub</TabsTrigger>
            </TabsList>

            <!-- ================= PRODUCTS TAB ================= -->
            <TabsContent value="products" class="space-y-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Products</CardTitle>
                            <CardDescription>Product details aur unke attributes manage karein.</CardDescription>
                        </div>
                        <Button @click="openProductModal()">Add Product</Button>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Image</TableHead> <!-- NAYA COLUMN -->
                                    <TableHead>Name</TableHead>
                                    <TableHead>Description</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead class="text-right">Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in products" :key="item.id">
                                    <TableCell>{{ item.id }}</TableCell>
                                    
                                    <!-- IMAGE SHOW KARNE KA LOGIC -->
                                    <TableCell>
                                        <div class="flex gap-2 max-w-[120px] overflow-x-auto py-1">
                                            <template v-for="img in product_images.filter(i => i.product_id === item.id)" :key="img.id">
                                                <img :src="img.url" :alt="img.alt" class="h-10 w-10 object-cover rounded-md border" />
                                            </template>
                                            <span v-if="!product_images.some(i => i.product_id === item.id)" class="text-xs text-muted-foreground italic">No image</span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="font-medium">{{ item.name }}</TableCell>
                                    <TableCell class="truncate max-w-[200px]">{{ item.dics }}</TableCell>
                                    <TableCell>${{ item.price }}</TableCell>
                                    <TableCell>${{ item.discount_price || '0' }}</TableCell>
                                    <TableCell class="text-right space-x-2">
                                        <Button variant="outline" size="icon" @click="openProductModal(item)">
                                            Edit
                                        </Button>
                                        <Button variant="destructive" size="icon" @click="deleteItem('products', item.id)">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- ================= CATEGORIES TAB ================= -->
            <TabsContent value="categories">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle>Categories</CardTitle>
                            <Button size="sm" @click="openAttributeModal('categories', 'Category')">Add</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>ID</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead class="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="cat in categories" :key="cat.id">
                                        <TableCell>{{ cat.id }}</TableCell>
                                        <TableCell>{{ cat.name }}</TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-700" @click="deleteItem('categories', cat.id)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between">
                            <CardTitle>Subcategories</CardTitle>
                            <Button size="sm" @click="openAttributeModal('subcategories', 'Subcategory')">Add</Button>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>ID</TableHead>
                                        <TableHead>Subcategory</TableHead>
                                        <TableHead>Main Category</TableHead>
                                        <TableHead class="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="sub in subcategories" :key="sub.id">
                                        <TableCell>{{ sub.id }}</TableCell>
                                        <TableCell>{{ sub.name }}</TableCell>
                                        <TableCell>{{ getCategoryName(sub.category_id) }}</TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="icon" class="text-red-500 hover:text-red-700" @click="deleteItem('subcategories', sub.id)">
                                                <Trash2 class="w-4 h-4" />
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>
        </Tabs>

        <!-- ================= PRODUCT ADD/EDIT MODAL ================= -->
        <Dialog v-model:open="isProductModalOpen">
            <DialogContent class="sm:max-w-[700px] max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{{ productForm.id ? 'Edit' : 'Add' }} Product</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="saveProduct" class="space-y-4 mt-4">
                    
                    <div class="space-y-2 border-b pb-4">
                        <Label class="flex items-center gap-2">
                            <ImageIcon class="w-4 h-4" /> Product Images
                        </Label>
                        <Input 
                            type="file" 
                            multiple 
                            accept="image/*" 
                            @change="handleImageUpload" 
                            class="cursor-pointer"
                        />
                        <p class="text-xs text-muted-foreground">Select multiple images at once to upload.</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Product Name</Label>
                            <Input v-model="productForm.name" required />
                        </div>
                        <div class="space-y-2">
                            <Label>Subcategory</Label>
                            <select v-model="productForm.sub_category_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <option value="" disabled>Select Subcategory...</option>
                                <option v-for="sub in subcategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label>Description</Label>
                        <Input v-model="productForm.dics" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label>Price</Label>
                            <Input type="number" step="0.01" v-model="productForm.price" required />
                        </div>
                        <div class="space-y-2">
                            <Label>Discount Price</Label>
                            <Input type="number" step="0.01" v-model="productForm.discount_price" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 pt-4 border-t mt-4">
                        <div>
                            <Label class="text-md font-semibold mb-2 block">Colors</Label>
                            <div class="flex flex-col gap-2 max-h-32 overflow-y-auto border p-2 rounded">
                                <label v-for="color in product_color" :key="color.id" class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="checkbox" :value="color.id" v-model="productForm.colors" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    {{ color.name }}
                                </label>
                            </div>
                        </div>
                        <div>
                            <Label class="text-md font-semibold mb-2 block">Sizes</Label>
                            <div class="flex flex-col gap-2 max-h-32 overflow-y-auto border p-2 rounded">
                                <label v-for="size in product_sizes" :key="size.id" class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="checkbox" :value="size.id" v-model="productForm.sizes" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    {{ size.name }} ({{ size.abriviation }})
                                </label>
                            </div>
                        </div>
                        <div>
                            <Label class="text-md font-semibold mb-2 block">Materials</Label>
                            <div class="flex flex-col gap-2 max-h-32 overflow-y-auto border p-2 rounded">
                                <label v-for="mat in product_material" :key="mat.id" class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="checkbox" :value="mat.id" v-model="productForm.materials" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    {{ mat.name }}
                                </label>
                            </div>
                        </div>
                        <div>
                            <Label class="text-md font-semibold mb-2 block">Finishes</Label>
                            <div class="flex flex-col gap-2 max-h-32 overflow-y-auto border p-2 rounded">
                                <label v-for="finish in product_finish" :key="finish.id" class="flex items-center gap-2 cursor-pointer text-sm">
                                    <input type="checkbox" :value="finish.id" v-model="productForm.finishes" class="rounded border-gray-300 text-primary focus:ring-primary">
                                    {{ finish.name }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <Button type="submit" class="w-full mt-4" :disabled="productForm.processing">Save Product</Button>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ================= ATTRIBUTES SETTINGS MODAL (Global) ================= -->
        <Dialog v-model:open="isAttributeSettingsOpen">
            <DialogContent class="sm:max-w-[1000px] max-h-[85vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-2xl">Global Attributes Settings</DialogTitle>
                </DialogHeader>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-4 items-start">
                    
                    <Card class="max-h-[60vh] flex flex-col overflow-hidden shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-2 bg-muted/30">
                            <CardTitle class="text-sm font-semibold">Colors</CardTitle>
                            <Button size="icon" variant="ghost" class="h-8 w-8" @click="openAttributeModal('product_color', 'Color')"><Plus class="w-4 h-4" /></Button>
                        </CardHeader>
                        <CardContent class="overflow-y-auto pt-2">
                            <ul class="space-y-1">
                                <li v-for="color in product_color" :key="color.id" class="flex justify-between items-center p-2 border-b text-sm">
                                    {{ color.name }}
                                    <Trash2 class="w-4 h-4 text-red-500 cursor-pointer hover:text-red-700" @click="deleteItem('product_color', color.id)" />
                                </li>
                                <li v-if="!product_color?.length" class="text-xs text-muted-foreground p-2 text-center">No colors added.</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card class="max-h-[60vh] flex flex-col overflow-hidden shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-2 bg-muted/30">
                            <CardTitle class="text-sm font-semibold">Sizes</CardTitle>
                            <Button size="icon" variant="ghost" class="h-8 w-8" @click="openAttributeModal('product_sizes', 'Size')"><Plus class="w-4 h-4" /></Button>
                        </CardHeader>
                        <CardContent class="overflow-y-auto pt-2">
                            <ul class="space-y-1">
                                <li v-for="size in product_sizes" :key="size.id" class="flex justify-between items-center p-2 border-b text-sm">
                                    <span>{{ size.name }} <span class="text-xs text-muted-foreground">({{ size.abriviation }})</span></span>
                                    <Trash2 class="w-4 h-4 text-red-500 cursor-pointer hover:text-red-700" @click="deleteItem('product_sizes', size.id)" />
                                </li>
                                <li v-if="!product_sizes?.length" class="text-xs text-muted-foreground p-2 text-center">No sizes added.</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card class="max-h-[60vh] flex flex-col overflow-hidden shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-2 bg-muted/30">
                            <CardTitle class="text-sm font-semibold">Materials</CardTitle>
                            <Button size="icon" variant="ghost" class="h-8 w-8" @click="openAttributeModal('product_material', 'Material')"><Plus class="w-4 h-4" /></Button>
                        </CardHeader>
                        <CardContent class="overflow-y-auto pt-2">
                            <ul class="space-y-1">
                                <li v-for="mat in product_material" :key="mat.id" class="flex justify-between items-center p-2 border-b text-sm">
                                    {{ mat.name }}
                                    <Trash2 class="w-4 h-4 text-red-500 cursor-pointer hover:text-red-700" @click="deleteItem('product_material', mat.id)" />
                                </li>
                                <li v-if="!product_material?.length" class="text-xs text-muted-foreground p-2 text-center">No materials added.</li>
                            </ul>
                        </CardContent>
                    </Card>

                    <Card class="max-h-[60vh] flex flex-col overflow-hidden shadow-sm">
                        <CardHeader class="flex flex-row items-center justify-between pb-2 bg-muted/30">
                            <CardTitle class="text-sm font-semibold">Finishes</CardTitle>
                            <Button size="icon" variant="ghost" class="h-8 w-8" @click="openAttributeModal('product_finish', 'Finish')"><Plus class="w-4 h-4" /></Button>
                        </CardHeader>
                        <CardContent class="overflow-y-auto pt-2">
                            <ul class="space-y-1">
                                <li v-for="finish in product_finish" :key="finish.id" class="flex justify-between items-center p-2 border-b text-sm">
                                    {{ finish.name }}
                                    <Trash2 class="w-4 h-4 text-red-500 cursor-pointer hover:text-red-700" @click="deleteItem('product_finish', finish.id)" />
                                </li>
                                <li v-if="!product_finish?.length" class="text-xs text-muted-foreground p-2 text-center">No finishes added.</li>
                            </ul>
                        </CardContent>
                    </Card>

                </div>
            </DialogContent>
        </Dialog>

        <!-- ================= GENERIC ATTRIBUTE MODAL ================= -->
        <Dialog v-model:open="isAttributeModalOpen">
            <DialogContent class="sm:max-w-[400px]">
                <DialogHeader>
                    <DialogTitle>Add {{ currentAttributeTitle }}</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="saveAttribute" class="space-y-4 mt-4">
                    <div class="space-y-2">
                        <Label>Name</Label>
                        <Input v-model="attributeForm.name" required />
                    </div>
                    
                    <div v-if="currentAttributeType === 'product_sizes'" class="space-y-2">
                        <Label>Abbreviation (e.g. XL, MD)</Label>
                        <Input v-model="attributeForm.abriviation" />
                    </div>

                    <div v-if="currentAttributeType === 'subcategories'" class="space-y-2">
                        <Label>Select Category</Label>
                        <select v-model="attributeForm.category_id" required class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                            <option value="" disabled>Select a Category...</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>

                    <Button type="submit" class="w-full" :disabled="attributeForm.processing">Save {{ currentAttributeTitle }}</Button>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>