import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import { copyFileSync, mkdirSync, readdirSync, statSync } from "fs";
import { join, dirname } from "path";

// Plugin to copy resources/images to build output
const copyImagesPlugin = () => {
    return {
        name: "copy-images",
        writeBundle() {
            const sourceDir = "resources/images";
            const targetDir = "public/build/images";

            // Create target directory if it doesn't exist
            try {
                mkdirSync(targetDir, { recursive: true });
            } catch (err) {
                // Directory might already exist
            }

            // Copy all files from source to target
            const copyRecursively = (src, dest) => {
                try {
                    const items = readdirSync(src);

                    for (const item of items) {
                        const srcPath = join(src, item);
                        const destPath = join(dest, item);

                        if (statSync(srcPath).isDirectory()) {
                            mkdirSync(destPath, { recursive: true });
                            copyRecursively(srcPath, destPath);
                        } else {
                            copyFileSync(srcPath, destPath);
                        }
                    }
                } catch (err) {
                    console.warn(`Could not copy images: ${err.message}`);
                }
            };

            copyRecursively(sourceDir, targetDir);
        },
    };
};

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: [`resources/views/**/*`],
        }),
        tailwindcss(),
        copyImagesPlugin(),
    ],
    server: {
        cors: true,
    },
});
