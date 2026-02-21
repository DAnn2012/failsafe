const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

// Configuration
const pluginName = 'failsafe-fatal-error-recovery';
const sourceDir = path.resolve(__dirname, '..');
const outputFile = path.resolve(sourceDir, `${pluginName}.zip`);

// Only include these files and directories in the zip
const includePaths = [
    'failsafe.php',
    'readme.txt',
    'includes',
    'templates',
    'languages',
    'assets',
    'build',
    'mu-plugin'
];

// Exclude these development files and directories
const excludePaths = [
    'node_modules',
    'src',
    'scripts',
    'webpack.config.js',
    'package.json',
    'package-lock.json',
    '.git',
    '.gitignore',
    'README.md',
    'failsafe.zip',
    'failsafe.tar.gz',
    'test-extract'
];

console.log('🚀 Building FailSafe plugin zip file...');
console.log(`📁 Source directory: ${sourceDir}`);
console.log(`📦 Output file: ${outputFile}`);

// Create a file to stream archive data to
const output = fs.createWriteStream(outputFile);
const archive = archiver('zip', {
    zlib: { level: 9 } // Sets the compression level
});

// Listen for all archive data to be written
output.on('close', () => {
    const sizeInMB = (archive.pointer() / 1024 / 1024).toFixed(2);
    console.log(`✅ Zip file created successfully!`);
    console.log(`📊 Total size: ${sizeInMB} MB`);
    console.log(`📁 Plugin folder: ${pluginName}/`);
    console.log(`📁 Files included: ${includePaths.join(', ')}`);
});

// Good practice to catch warnings (ie stat failures and other non-blocking errors)
archive.on('warning', (err) => {
    if (err.code === 'ENOENT') {
        console.warn('⚠️  Warning:', err.message);
    } else {
        throw err;
    }
});

// Good practice to catch this error explicitly
archive.on('error', (err) => {
    throw err;
});

// Pipe archive data to the file
archive.pipe(output);

// Helper function to check if a path should be excluded
function shouldExclude(filePath) {
    return excludePaths.some(excludePath => 
        filePath.includes(excludePath) || 
        filePath.startsWith(excludePath + path.sep)
    );
}

// Helper function to add files and directories recursively
function addToArchive(dirPath, archivePath = '') {
    const items = fs.readdirSync(dirPath);
    
    for (const item of items) {
        const fullPath = path.join(dirPath, item);
        const relativePath = path.join(archivePath, item);
        
        // Skip excluded paths
        if (shouldExclude(fullPath)) {
            continue;
        }
        
        const stat = fs.statSync(fullPath);
        
        if (stat.isDirectory()) {
            // Add directory
            archive.directory(fullPath, relativePath);
        } else if (stat.isFile()) {
            // Add file
            archive.file(fullPath, { name: relativePath });
        }
    }
}

// Add only the necessary WordPress plugin files
console.log('📦 Adding WordPress plugin files to archive...');
for (const includePath of includePaths) {
    const fullPath = path.join(sourceDir, includePath);
    if (fs.existsSync(fullPath)) {
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            archive.directory(fullPath, path.join(pluginName, includePath));
        } else {
            archive.file(fullPath, { name: path.join(pluginName, includePath) });
        }
        console.log(`✅ Added: ${includePath}`);
    } else {
        console.warn(`⚠️  Warning: ${includePath} not found`);
    }
}

// Finalize the archive
archive.finalize();